<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Configuration\ConfigurationService;
use App\DataFixtures\UserFixtures;
use App\Entity\Configuration;
use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Repository\UserRepository;
use App\Tests\KernelTestTrait;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Test\Constraint as ResponseConstraint;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * ControllerBaseTest adds some useful functions for writing integration tests.
 */
abstract class AbstractControllerBaseTestCase extends WebTestCase
{
    use KernelTestTrait;

    public const DEFAULT_LANGUAGE = 'en';
    public const DEFAULT_DATE_FORMAT = 'n/j/Y';
    public const DEFAULT_TIME_FORMAT = 'h:mm a';

    protected function tearDown(): void
    {
        $this->clearConfigCache();
        parent::tearDown();
    }

    protected function formatDateRange(\DateTime $begin, \DateTime $end): string
    {
        return $begin->format(self::DEFAULT_DATE_FORMAT) . DateRangeType::DATE_SPACER . $end->format(self::DEFAULT_DATE_FORMAT);
    }

    protected function formatDate(\DateTime $date): string
    {
        return $date->format(self::DEFAULT_DATE_FORMAT);
    }

    protected function formatDateTime(\DateTime $date): string
    {
        return $this->formatDate($date) . ' ' . $this->formatTime($date);
    }

    protected function formatTime(\DateTime $date): string
    {
        return $date->format(self::DEFAULT_TIME_FORMAT);
    }

    /**
     * Using a special container, to access private services as well.
     *
     * @param string $service
     * @return object|null
     * @see https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function getPrivateService(string $service)
    {
        return self::getContainer()->get($service);
    }

    protected function loadUserFromDatabase(string $username): User
    {
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        /** @var UserRepository $userRepository */
        $userRepository = $doctrine->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier($username);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    protected function setSystemConfiguration(string $name, $value): void
    {
        /** @var ConfigurationService $repository */
        $repository = self::getContainer()->get(ConfigurationService::class);

        $entity = $repository->getConfiguration($name);
        if ($entity === null) {
            $entity = new Configuration();
            $entity->setName($name);
        }
        $entity->setValue($value);
        $repository->saveConfiguration($entity);
        $this->clearConfigCache();
    }

    protected function clearConfigCache(): void
    {
        /** @var ConfigurationService $service */
        $service = self::getContainer()->get(ConfigurationService::class);
        $service->clearCache();
    }

    protected function getClientForAuthenticatedUser(string $role = User::ROLE_USER): HttpKernelBrowser
    {
        $username = match ($role) {
            User::ROLE_SUPER_ADMIN => UserFixtures::USERNAME_SUPER_ADMIN,
            User::ROLE_ADMIN => UserFixtures::USERNAME_ADMIN,
            User::ROLE_TEAMLEAD => UserFixtures::USERNAME_TEAMLEAD,
            User::ROLE_USER => UserFixtures::USERNAME_USER,
            default => null,
        };

        if ($username === null) {
            throw new \Exception('Unknown username: ' . $username);
        }

        return $this->loginByUsername($username);
    }

    protected function loginByUsername(string $username): HttpKernelBrowser
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = $this->getPrivateService(UserRepository::class);
        $user = $userRepository->findByUsername($username);
        if ($user === null) {
            throw new \Exception('Unknown user: ' . $username);
        }

        $client->loginUser($user, 'secured_area');

        return $client;
    }

    protected function loginUser(User $user): HttpKernelBrowser
    {
        $client = static::createClient();
        $client->loginUser($user, 'secured_area');

        return $client;
    }

    /**
     * @return non-empty-string
     */
    protected function createUrl(string $url): string
    {
        $prefix = '/' . self::DEFAULT_LANGUAGE;

        if (!str_starts_with($url, $prefix)) {
            $url = $prefix . '/' . ltrim($url, '/');
        }

        return $url;
    }

    public function request(HttpKernelBrowser $client, string $url, string $method = 'GET', array $parameters = [], string $content = null): Crawler
    {
        return $client->request($method, $this->createUrl($url), $parameters, [], [], $content);
    }

    public function requestPure(HttpKernelBrowser $client, string $url, string $method = 'GET', array $parameters = [], string $content = null): Crawler
    {
        return $client->request($method, $url, $parameters, [], [], $content);
    }

    protected function assertRequestIsSecured(HttpKernelBrowser $client, string $url, string $method = 'GET'): void
    {
        $this->request($client, $url, $method);

        /** @var RedirectResponse $response */
        $response = $client->getResponse();

        self::assertTrue(
            $response->isRedirect(),
            \sprintf('The URL %s is not protected (%s occurred).', $url, $response->getStatusCode())
        );

        self::assertStringEndsWith(
            '/login',
            $response->getTargetUrl(),
            \sprintf('The URL %s does not redirect to the login form.', $url)
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    protected function assertSuccessResponse(HttpKernelBrowser $client, string $message = ''): void
    {
        $response = $client->getResponse();
        self::assertThat($response, new ResponseConstraint\ResponseIsSuccessful(), 'Response is not successful, got code: ' . $response->getStatusCode());
    }

    protected function assertUrlIsSecured(string $url, string $method = 'GET'): void
    {
        $client = self::createClient();
        $this->assertRequestIsSecured($client, $url, $method);
    }

    protected function assertUrlIsSecuredForRole(string $role, string $url, string $method = 'GET'): void
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $client->request($method, $this->createUrl($url));
        self::assertFalse(
            $client->getResponse()->isSuccessful(),
            \sprintf('The secure URL %s is not protected for role %s', $url, $role)
        );
        $this->assertAccessDenied($client);
    }

    protected function assertAccessDenied(HttpKernelBrowser $client): void
    {
        self::assertFalse(
            $client->getResponse()->isSuccessful(),
            'Access is not denied for URL: ' . $client->getRequest()->getUri()
        );
        self::assertStringContainsString(
            'Page is restricted',
            $client->getResponse()->getContent(),
            'Could not find AccessDeniedException in response'
        );
    }

    protected function assertAccessIsGranted(HttpKernelBrowser $client, string $url, string $method = 'GET', array $parameters = []): void
    {
        $this->request($client, $url, $method, $parameters);
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    protected function assertRouteNotFound(HttpKernelBrowser $client): void
    {
        self::assertFalse($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    protected function assertMainContentClass(HttpKernelBrowser $client, string $classname): void
    {
        self::assertStringContainsString('<section id="" class="content ' . $classname . '">', $client->getResponse()->getContent());
    }

    /**
     * @param HttpKernelBrowser $client
     */
    protected function assertHasDataTable(HttpKernelBrowser $client): void
    {
        self::assertStringContainsString('<table class="table table-hover dataTable" role="grid" data-reload-event="', $client->getResponse()->getContent());
    }

    /**
     * @param HttpKernelBrowser $client
     */
    protected static function assertHasProgressbar(HttpKernelBrowser $client): void
    {
        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('<div class="progress-bar', $content);
        self::assertStringContainsString('" role="progressbar" aria-valuenow="', $content);
        self::assertStringContainsString('" aria-valuemin="0" aria-valuemax="100" style="width: ', $content);
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string $class
     * @param int $count
     */
    protected function assertDataTableRowCount(HttpKernelBrowser $client, string $class, int $count): void
    {
        $node = $client->getCrawler()->filter('section.content div.' . $class . ' table.dataTable tbody tr:not(.summary)');
        self::assertEquals($count, $node->count());
    }

    /**
     * @param HttpKernelBrowser $client
     * @param array $buttons
     */
    protected function assertPageActions(HttpKernelBrowser $client, array $buttons): void
    {
        $node = $client->getCrawler()->filter('div.page-header div.page-actions .pa-desktop a');

        /** @var \DOMElement $element */
        foreach ($node->getIterator() as $element) {
            $expectedClass = trim(str_replace(['btn action-', ' btn-icon', 'btn btn-primary action-', 'btn btn-dark action-', 'btn btn-white action-', 'btn  action-'], '', $element->getAttribute('class')));
            self::assertArrayHasKey($expectedClass, $buttons);
            $expectedUrl = $buttons[$expectedClass];
            self::assertEquals($expectedUrl, $element->getAttribute('href'));
        }

        self::assertEquals(\count($buttons), $node->count(), 'Invalid amount of page actions');
    }

    /**
     * @param HttpKernelBrowser $client the client to use
     * @param string $url the URL of the page displaying the initial form to submit
     * @param string $formSelector a selector to find the form to test
     * @param array $formData values to fill in the form
     * @param array $fieldNames array of form-fields that should fail
     * @param bool $disableValidation whether the form should validate before submitting or not
     */
    protected function assertHasValidationError(HttpKernelBrowser $client, string $url, string $formSelector, array $formData, array $fieldNames, bool $disableValidation = true): void
    {
        $crawler = $client->request('GET', $this->createUrl($url));
        $form = $crawler->filter($formSelector)->form();
        if ($disableValidation) {
            $form->disableValidation();
        }
        $result = $client->submit($form, $formData);

        $submittedForm = $result->filter($formSelector);
        $validationErrors = $submittedForm->filter('div.invalid-feedback.d-block');

        self::assertEquals(
            \count($fieldNames),
            \count($validationErrors),
            \sprintf('Expected %s validation errors, found %s', \count($fieldNames), \count($validationErrors))
        );

        foreach ($fieldNames as $name) {
            $field = $submittedForm->filter($name);
            self::assertGreaterThan(0, $field->count(), 'Could not find form field: ' . $name);
            $list = $field->nextAll();
            $validation = $list->filter('li.text-danger');
            if (\count($validation) < 1) {
                // decorated form fields with icon have a different html structure
                /** @var \DOMElement $listMsg */
                $listMsg = $field->getNode(0); //->parents()->getNode(1);
                $classes = $listMsg->getAttribute('class');
                self::assertStringContainsString('is-invalid', $classes, 'Form field has no validation message: ' . $name);
            }
        }
    }

    protected function assertFormHasValidationError(string $role, string $url, string $formSelector, array $formData, array $fieldNames): void
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->assertHasValidationError($client, $url, $formSelector, $formData, $fieldNames);
    }

    /**
     * @param HttpKernelBrowser $client
     */
    protected function assertHasNoEntriesWithFilter(HttpKernelBrowser $client): void
    {
        $this->assertCalloutWidgetWithMessage($client, 'No entries were found based on your selected filters.');
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string $message
     */
    protected function assertCalloutWidgetWithMessage(HttpKernelBrowser $client, string $message): void
    {
        $node = $client->getCrawler()->filter('div.alert.alert-warning.alert-important');
        self::assertStringContainsString($message, $node->text(null, true));
    }

    protected function assertHasFlashDeleteSuccess(HttpKernelBrowser $client): void
    {
        $this->assertHasFlashSuccess($client, 'Entry was deleted');
    }

    protected function assertHasFlashSaveSuccess(HttpKernelBrowser $client): void
    {
        $this->assertHasFlashSuccess($client, 'Saved changes');
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string|null $message
     */
    protected function assertHasFlashSuccess(HttpKernelBrowser $client, string $message = null): void
    {
        $this->assertHasFlashMessage($client, 'success', $message);
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string|null $message
     */
    protected function assertHasFlashError(HttpKernelBrowser $client, string $message = null): void
    {
        $this->assertHasFlashMessage($client, 'error', $message);
    }

    private function assertHasFlashMessage(HttpKernelBrowser $client, string $type, string $message = null): void
    {
        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('ALERT.' . $type . '(\'', $content, 'Could not find flash ' . $type . ' message');
        if (null !== $message) {
            // this is a lazy workaround, the templates use the javascript escape filter: |e('js')
            // if you ever want to test more complex strings, this logic has to be enhanced
            $message = str_replace([' ', ':'], ['\u0020', '\u003A'], $message);
            self::assertStringContainsString($message, $content);
        }
    }

    protected function assertIsRedirect(HttpKernelBrowser $client, ?string $url = null, bool $endsWith = true): void
    {
        self::assertResponseRedirects();

        if (null === $url) {
            return;
        }

        $this->assertRedirectUrl($client, $url, $endsWith);
    }

    protected function assertIsModalRedirect(HttpKernelBrowser $client, ?string $url = null, bool $endsWith = true): string
    {
        self::assertEquals(201, $client->getResponse()->getStatusCode());
        self::assertTrue($client->getResponse()->headers->has('x-modal-redirect'), 'Could not find "x-modal-redirect" header');
        $location = $client->getResponse()->headers->get('x-modal-redirect');
        self::assertNotNull($location);

        // check for meta refresh
        $expectedMeta = \sprintf('<meta http-equiv="refresh" content="0;url=\'%1$s\'" />', $location);
        self::assertStringContainsString($expectedMeta, $client->getResponse()->getContent());

        if ($url !== null) {
            if ($endsWith && $url !== '') {
                self::assertStringEndsWith($url, $location, 'Redirect URL does not match');
            } else {
                self::assertStringContainsString($url, $location, 'Redirect URL does not match');
            }
        }

        return $location;
    }

    protected function assertRedirectUrl(HttpKernelBrowser $client, string $url, bool $endsWith = true): void
    {
        self::assertTrue($client->getResponse()->headers->has('Location'), 'Could not find "Location" header');
        $location = $client->getResponse()->headers->get('Location');
        self::assertNotNull($location);

        if ($endsWith && $url !== '') {
            self::assertStringEndsWith($url, $location, 'Redirect URL does not match');
        } else {
            self::assertStringContainsString($url, $location, 'Redirect URL does not match');
        }
    }

    protected function assertExcelExportResponse(HttpKernelBrowser $client, string $prefix): void
    {
        /** @var BinaryFileResponse $response */
        $response = $client->getResponse();
        self::assertInstanceOf(BinaryFileResponse::class, $response);

        $disposition = $response->headers->get('Content-Disposition');
        self::assertNotNull($disposition);

        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename=' . $prefix, $disposition);
        self::assertStringContainsString('.xlsx', $disposition);
    }

    protected function assertInvalidCsrfToken(HttpKernelBrowser $client, string $url, string $expectedRedirect): void
    {
        $this->request($client, $url);

        $this->assertIsRedirect($client);
        $this->assertRedirectUrl($client, $expectedRedirect);
        $client->followRedirect();
        $this->assertHasFlashError($client, 'The action could not be performed: invalid security token.');
    }

    protected function getCsrfToken(HttpKernelBrowser $client, string $name): CsrfToken
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $stack */
        $stack = self::getContainer()->get(RequestStack::class);
        $stack->push($request);
        /** @var CsrfTokenManager $tokenManager */
        $tokenManager = self::getContainer()->get('security.csrf.token_manager');

        return $tokenManager->getToken($name);
    }
}
