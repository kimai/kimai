<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\KernelTestTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * ControllerBaseTest adds some useful functions for writing integration tests.
 */
abstract class ControllerBaseTest extends WebTestCase
{
    use KernelTestTrait;

    public const DEFAULT_LANGUAGE = 'en';

    protected function getClientForAuthenticatedUser(string $role = User::ROLE_USER): HttpKernelBrowser
    {
        switch ($role) {
            case User::ROLE_SUPER_ADMIN:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => UserFixtures::USERNAME_SUPER_ADMIN,
                    'PHP_AUTH_PW' => UserFixtures::DEFAULT_PASSWORD,
                ]);
                break;

            case User::ROLE_ADMIN:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => UserFixtures::USERNAME_ADMIN,
                    'PHP_AUTH_PW' => UserFixtures::DEFAULT_PASSWORD,
                ]);
                break;

            case User::ROLE_TEAMLEAD:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => UserFixtures::USERNAME_TEAMLEAD,
                    'PHP_AUTH_PW' => UserFixtures::DEFAULT_PASSWORD,
                ]);
                break;

            case User::ROLE_USER:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => UserFixtures::USERNAME_USER,
                    'PHP_AUTH_PW' => UserFixtures::DEFAULT_PASSWORD,
                ]);
                break;

            default:
                $client = null;
                break;
        }

        return $client;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function createUrl($url)
    {
        return '/' . self::DEFAULT_LANGUAGE . '/' . ltrim($url, '/');
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param string $content
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function request(HttpKernelBrowser $client, string $url, $method = 'GET', array $parameters = [], string $content = null)
    {
        return $client->request($method, $this->createUrl($url), $parameters, [], [], $content);
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string $url
     * @param string $method
     */
    protected function assertRequestIsSecured(HttpKernelBrowser $client, string $url, ?string $method = 'GET')
    {
        $this->request($client, $url, $method);

        /** @var RedirectResponse $response */
        $response = $client->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);

        self::assertTrue(
            $response->isRedirect(),
            sprintf('The secure URL %s is not protected.', $url)
        );

        self::assertStringEndsWith(
            '/login',
            $response->getTargetUrl(),
            sprintf('The secure URL %s does not redirect to the login form.', $url)
        );
    }

    /**
     * @param string $url
     * @param string $method
     */
    protected function assertUrlIsSecured(string $url, $method = 'GET')
    {
        $client = self::createClient();
        $this->assertRequestIsSecured($client, $url, $method);
    }

    /**
     * @param string $role
     * @param string $url
     * @param string $method
     */
    protected function assertUrlIsSecuredForRole(string $role, string $url, string $method = 'GET')
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $client->request($method, $this->createUrl($url));
        self::assertFalse(
            $client->getResponse()->isSuccessful(),
            sprintf('The secure URL %s is not protected for role %s', $url, $role)
        );
        $this->assertAccessDenied($client);
    }

    protected function assertAccessDenied(HttpKernelBrowser $client)
    {
        self::assertFalse(
            $client->getResponse()->isSuccessful(),
            'Access is not denied for URL: ' . $client->getRequest()->getUri()
        );
        self::assertStringContainsString(
            'Symfony\Component\Security\Core\Exception\AccessDeniedException',
            $client->getResponse()->getContent(),
            'Could not find AccessDeniedException in response'
        );
    }

    protected function assertAccessIsGranted(HttpKernelBrowser $client, string $url, string $method = 'GET', array $parameters = [])
    {
        $this->request($client, $url, $method, $parameters);
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    protected function assertRouteNotFound(HttpKernelBrowser $client)
    {
        self::assertFalse($client->getResponse()->isSuccessful());
        self::assertEquals(404, $client->getResponse()->getStatusCode());
    }

    protected function assertMainContentClass(HttpKernelBrowser $client, string $classname)
    {
        self::assertStringContainsString('<section class="content ' . $classname . '">', $client->getResponse()->getContent());
    }

    /**
     * @param HttpKernelBrowser $client
     */
    protected function assertHasDataTable(HttpKernelBrowser $client)
    {
        self::assertStringContainsString('<table class="table table-striped table-hover dataTable" role="grid" data-reload-event="', $client->getResponse()->getContent());
    }

    /**
     * @param HttpKernelBrowser $client
     */
    protected static function assertHasProgressbar(HttpKernelBrowser $client)
    {
        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('<div class="progress-bar progress-bar-', $content);
        self::assertStringContainsString('" role="progressbar" aria-valuenow="', $content);
        self::assertStringContainsString('" aria-valuemin="0" aria-valuemax="100" style="width: ', $content);
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string $id
     * @param int $count
     */
    protected function assertDataTableRowCount(HttpKernelBrowser $client, string $id, int $count)
    {
        $node = $client->getCrawler()->filter('section.content div#' . $id . ' table.table-striped tbody tr:not(.summary)');
        self::assertEquals($count, $node->count());
    }

    /**
     * @param HttpKernelBrowser $client
     * @param array $buttons
     */
    protected function assertPageActions(HttpKernelBrowser $client, array $buttons)
    {
        $node = $client->getCrawler()->filter('section.content-header div.breadcrumb div.box-tools div.btn-group a');

        /** @var \DOMElement $element */
        foreach ($node->getIterator() as $element) {
            $expectedClass = str_replace('btn btn-default btn-', '', $element->getAttribute('class'));
            self::assertArrayHasKey($expectedClass, $buttons);
            $expectedUrl = $buttons[$expectedClass];
            self::assertEquals($expectedUrl, $element->getAttribute('href'));
        }

        self::assertEquals(\count($buttons), $node->count(), 'Invalid amount of page actions');
    }

    /**
     * @param string $role the USER role to use for the request
     * @param string $url the URL of the page displaying the initial form to submit
     * @param string $formSelector a selector to find the form to test
     * @param array $formData values to fill in the form
     * @param array $fieldNames array of form-fields that should fail
     * @param bool $disableValidation whether the form should validate before submitting or not
     */
    protected function assertFormHasValidationError($role, $url, $formSelector, array $formData, array $fieldNames, $disableValidation = true)
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $crawler = $client->request('GET', $this->createUrl($url));
        $form = $crawler->filter($formSelector)->form();
        if ($disableValidation) {
            $form->disableValidation();
        }
        $result = $client->submit($form, $formData);

        $submittedForm = $result->filter($formSelector);
        $validationErrors = $submittedForm->filter('li.text-danger');

        self::assertEquals(
            \count($fieldNames),
            \count($validationErrors),
            sprintf('Expected %s validation errors, found %s', \count($fieldNames), \count($validationErrors))
        );

        foreach ($fieldNames as $name) {
            $field = $submittedForm->filter($name);
            self::assertNotNull($field, 'Could not find form field: ' . $name);
            $list = $field->nextAll();
            self::assertNotNull($list, 'Form field has no validation message: ' . $name);

            $validation = $list->filter('li.text-danger');
            if (\count($validation) < 1) {
                // decorated form fields with icon have a different html structure, see kimai-theme.html.twig
                /** @var \DOMElement $listMsg */
                $listMsg = $field->parents()->getNode(1);
                $classes = $listMsg->getAttribute('class');
                self::assertStringContainsString('has-error', $classes, 'Form field has no validation message: ' . $name);
            }
        }
    }

    /**
     * @param HttpKernelBrowser $client
     */
    protected function assertHasNoEntriesWithFilter(HttpKernelBrowser $client)
    {
        $this->assertCalloutWidgetWithMessage($client, 'No entries were found based on your selected filters.');
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string $message
     */
    protected function assertCalloutWidgetWithMessage(HttpKernelBrowser $client, string $message)
    {
        $node = $client->getCrawler()->filter('div.callout.callout-warning.lead');
        self::assertStringContainsString($message, $node->text(null, true));
    }

    protected function assertHasFlashDeleteSuccess(HttpKernelBrowser $client)
    {
        $this->assertHasFlashSuccess($client, 'Entry was deleted');
    }

    protected function assertHasFlashSaveSuccess(HttpKernelBrowser $client)
    {
        $this->assertHasFlashSuccess($client, 'Saved changes');
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string|null $message
     */
    protected function assertHasFlashSuccess(HttpKernelBrowser $client, string $message = null)
    {
        $node = $client->getCrawler()->filter('div.alert.alert-success.alert-dismissible');
        self::assertGreaterThan(0, $node->count(), 'Could not find flash success message');
        if (null !== $message) {
            self::assertStringContainsString($message, $node->text(null, true));
        }
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string|null $message
     */
    protected function assertHasFlashError(HttpKernelBrowser $client, string $message = null)
    {
        $node = $client->getCrawler()->filter('div.alert.alert-error.alert-dismissible');
        self::assertGreaterThan(0, $node->count(), 'Could not find flash error message');
        if (null !== $message) {
            self::assertStringContainsString($message, $node->text(null, true));
        }
    }

    /**
     * @param HttpKernelBrowser $client
     * @param string $url
     */
    protected function assertIsRedirect(HttpKernelBrowser $client, $url = null)
    {
        self::assertTrue($client->getResponse()->isRedirect(), 'Response is not a redirect');
        if (null === $url) {
            return;
        }

        self::assertTrue($client->getResponse()->headers->has('Location'), 'Could not find "Location" header');
        self::assertStringEndsWith($url, $client->getResponse()->headers->get('Location'), 'Redirect URL does not match');
    }
}
