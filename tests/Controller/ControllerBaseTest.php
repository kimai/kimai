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
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * ControllerBaseTest adds some useful functions for writing integration tests.
 */
abstract class ControllerBaseTest extends WebTestCase
{
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
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function request(Client $client, string $url, $method = 'GET')
    {
        return $client->request($method, $this->createUrl($url));
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $method
     */
    protected function assertRequestIsSecured(Client $client, string $url, $method = 'GET')
    {
        $client->request($method, $this->createUrl($url));

        /* @var RedirectResponse $response */
        $response = $client->getResponse();

        $this->assertTrue(
            $response->isRedirect(),
            sprintf('The secure URL %s is not protected.', $url . $response->getContent())
        );

        $this->assertEquals(
            'http://localhost' . $this->createUrl('/login'),
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
        $this->assertFalse(
            $client->getResponse()->isSuccessful(),
            sprintf('The secure URL %s is not protected for role %s', $url, $role)
        );
        $this->assertContains('Symfony\Component\Security\Core\Exception\AccessDeniedException', $client->getResponse()->getContent());
    }

    /**
     * @param Client $client
     * @param string $url
     */
    protected function assertAccessIsGranted(Client $client, $url)
    {
        $this->request($client, $url);
        $this->assertTrue($client->getResponse()->isSuccessful());
        // TODO improve this test?
    }

    /**
     * @param Client $client
     */
    protected function assertRouteNotFound(Client $client)
    {
        $this->assertFalse($client->getResponse()->isSuccessful());
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * @param Client $client
     * @param string $classname
     */
    protected function assertMainContentClass(Client $client, $classname)
    {
        $this->assertContains('<section class="content ' . $classname . '">', $client->getResponse()->getContent());
    }

    /**
     * @param Client $client
     */
    protected function assertHasDataTable(Client $client)
    {
        $this->assertContains('<table class="table table-striped table-hover dataTable" role="grid">', $client->getResponse()->getContent());
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

        $this->assertEquals(
            count($fieldNames),
            count($validationErrors),
            sprintf('Expected %s validation errors, found %s', count($fieldNames), count($validationErrors))
        );

        foreach ($fieldNames as $name) {
            $field = $submittedForm->filter($name);
            $this->assertNotNull($field, 'Could not find form field: ' . $name);
            $list = $field->nextAll();
            $this->assertNotNull($list, 'Form field has no validation message: ' . $name);
            $validation = $list->filter('li.text-danger');
            $this->assertGreaterThanOrEqual(1, count($validation), 'Form field has no validation message: ' . $name);
        }
    }

    /**
     * @param EntityManager $em
     * @param Fixture $fixture
     */
    protected function importFixture(EntityManager $em, Fixture $fixture)
    {
        $loader = new Loader();
        $loader->addFixture($fixture);

        $executor = new ORMExecutor($em, null);
        $executor->execute($loader->getFixtures(), true);
    }

    /**
     * @param EntityManager $em
     * @param string $role
     * @return User|null
     */
    protected function getUserByRole(EntityManager $em, string $role = User::ROLE_USER)
    {
        $name = null;

        switch ($role) {
            case User::ROLE_SUPER_ADMIN:
                $name = UserFixtures::USERNAME_SUPER_ADMIN;
                break;

            case User::ROLE_ADMIN:
                $name = UserFixtures::USERNAME_ADMIN;
                break;

            case User::ROLE_TEAMLEAD:
                $name = UserFixtures::USERNAME_TEAMLEAD;
                break;

            case User::ROLE_USER:
                $name = UserFixtures::USERNAME_USER;
                break;

            default:
                return null;
        }

        return $em->getRepository(User::class)->findOneBy(['username' => $name]);
    }
}
