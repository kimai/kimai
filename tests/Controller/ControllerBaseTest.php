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

/**
 * ControllerBaseTest adds some useful functions for writing integration tests.
 */
abstract class ControllerBaseTest extends WebTestCase
{
    use KernelTestTrait;

    public const DEFAULT_LANGUAGE = 'en';

    /**
     * @param string $role
     * @return Client
     */
    protected function getClientForAuthenticatedUser(string $role = User::ROLE_USER)
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
     * @param Client $client
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param string $content
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function request(Client $client, string $url, $method = 'GET', array $parameters = [], string $content = null)
    {
        return $client->request($method, $this->createUrl($url), $parameters, [], [], $content);
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $method
     */
    protected function assertRequestIsSecured(Client $client, string $url, ?string $method = 'GET')
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

    protected function assertAccessDenied(Client $client)
    {
        self::assertFalse(
            $client->getResponse()->isSuccessful(),
            'Access is not denied for URL: ' . $client->getRequest()->getUri()
        );
        self::assertContains(
            'Symfony\Component\Security\Core\Exception\AccessDeniedException',
            $client->getResponse()->getContent(),
            'Could not find AccessDeniedException in response'
        );
    }

    protected function assertAccessIsGranted(Client $client, string $url, string $method = 'GET', array $parameters = [])
    {
        $this->request($client, $url, $method, $parameters);
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    protected function assertRouteNotFound(Client $client)
    {
        self::assertFalse($client->getResponse()->isSuccessful());
        self::assertEquals(404, $client->getResponse()->getStatusCode());
    }

    protected function assertMainContentClass(Client $client, string $classname)
    {
        self::assertContains('<section class="content ' . $classname . '">', $client->getResponse()->getContent());
    }

    /**
     * @param Client $client
     */
    protected function assertHasDataTable(Client $client)
    {
        self::assertContains('<table class="table table-striped table-hover dataTable" role="grid" data-reload-event="', $client->getResponse()->getContent());
    }

    /**
     * @param Client $client
     */
    protected static function assertHasProgressbar(Client $client)
    {
        $content = $client->getResponse()->getContent();
        self::assertContains('<div class="progress-bar progress-bar-', $content);
        self::assertContains('" role="progressbar" aria-valuenow="', $content);
        self::assertContains('" aria-valuemin="0" aria-valuemax="100" style="width: ', $content);
    }

    /**
     * @param Client $client
     * @param string $id
     * @param int $count
     */
    protected function assertDataTableRowCount(Client $client, string $id, int $count)
    {
        $node = $client->getCrawler()->filter('section.content div#' . $id . ' table.table-striped tbody tr');
        self::assertEquals($count, $node->count());
    }

    /**
     * @param Client $client
     * @param array $buttons
     */
    protected function assertPageActions(Client $client, array $buttons)
    {
        $node = $client->getCrawler()->filter('section.content-header div.breadcrumb div.box-tools div.btn-group a.btn');

        foreach ($node->getIterator() as $element) {
            $expectedClass = str_replace('btn btn-default btn-', '', $element->getAttribute('class'));
            self::assertArrayHasKey($expectedClass, $buttons);
            $expectedUrl = $buttons[$expectedClass];
            self::assertEquals($expectedUrl, $element->getAttribute('href'));
        }
        
        self::assertEquals(count($buttons), $node->count(), 'Invalid amount of page actions');
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
            count($fieldNames),
            count($validationErrors),
            sprintf('Expected %s validation errors, found %s', count($fieldNames), count($validationErrors))
        );

        foreach ($fieldNames as $name) {
            $field = $submittedForm->filter($name);
            self::assertNotNull($field, 'Could not find form field: ' . $name);
            $list = $field->nextAll();
            self::assertNotNull($list, 'Form field has no validation message: ' . $name);

            $validation = $list->filter('li.text-danger');
            if (count($validation) < 1) {
                // decorated form fields with icon have a different html structure, see kimai-theme.html.twig
                $classes = $field->parents()->getNode(1)->getAttribute('class');
                self::assertContains('has-error', $classes, 'Form field has no validation message: ' . $name);
            }
        }
    }

    /**
     * @param Client $client
     */
    protected function assertHasNoEntriesWithFilter(Client $client)
    {
        $this->assertCalloutWidgetWithMessage($client, 'No entries were found based on your selected filters.');
    }

    /**
     * @param Client $client
     * @param string $message
     */
    protected function assertCalloutWidgetWithMessage(Client $client, string $message)
    {
        $node = $client->getCrawler()->filter('div.callout.callout-warning.lead');
        self::assertContains($message, $node->text());
    }

    protected function assertHasFlashDeleteSuccess(Client $client)
    {
        $this->assertHasFlashSuccess($client, 'Entry was deleted');
    }

    protected function assertHasFlashSaveSuccess(Client $client)
    {
        $this->assertHasFlashSuccess($client, 'Saved changes');
    }

    /**
     * @param Client $client
     * @param string|null $message
     */
    protected function assertHasFlashSuccess(Client $client, string $message = null)
    {
        $node = $client->getCrawler()->filter('div.alert.alert-success.alert-dismissible');
        self::assertNotEmpty($node->text());
        if (null !== $message) {
            self::assertContains($message, $node->text());
        }
    }

    /**
     * @param Client $client
     * @param string|null $message
     */
    protected function assertHasFlashError(Client $client, string $message = null)
    {
        $node = $client->getCrawler()->filter('div.alert.alert-error.alert-dismissible');
        self::assertNotEmpty($node->text());
        if (null !== $message) {
            self::assertContains($message, $node->text());
        }
    }

    /**
     * @param Client $client
     * @param string $url
     */
    protected function assertIsRedirect(Client $client, $url = null)
    {
        self::assertTrue($client->getResponse()->isRedirect());
        if (null === $url) {
            return;
        }

        self::assertTrue($client->getResponse()->headers->has('Location'));
        self::assertStringEndsWith($url, $client->getResponse()->headers->get('Location'));
    }
}
