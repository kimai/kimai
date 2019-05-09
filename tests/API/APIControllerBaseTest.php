<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\Controller\ControllerBaseTest;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds some useful functions for writing API integration tests.
 */
abstract class APIControllerBaseTest extends ControllerBaseTest
{
    /**
     * @param string $role
     * @return Client
     */
    protected function getClientForAuthenticatedUser(string $role = User::ROLE_USER)
    {
        switch ($role) {
            case User::ROLE_SUPER_ADMIN:
                $client = self::createClient([], [
                    'HTTP_X_AUTH_USER' => UserFixtures::USERNAME_SUPER_ADMIN,
                    'HTTP_X_AUTH_TOKEN' => UserFixtures::DEFAULT_API_TOKEN,
                ]);
                break;

            case User::ROLE_ADMIN:
                $client = self::createClient([], [
                    'HTTP_X_AUTH_USER' => UserFixtures::USERNAME_ADMIN,
                    'HTTP_X_AUTH_TOKEN' => UserFixtures::DEFAULT_API_TOKEN,
                ]);
                break;

            case User::ROLE_TEAMLEAD:
                $client = self::createClient([], [
                    'HTTP_X_AUTH_USER' => UserFixtures::USERNAME_TEAMLEAD,
                    'HTTP_X_AUTH_TOKEN' => UserFixtures::DEFAULT_API_TOKEN,
                ]);
                break;

            case User::ROLE_USER:
                $client = self::createClient([], [
                    'HTTP_X_AUTH_USER' => UserFixtures::USERNAME_USER,
                    'HTTP_X_AUTH_TOKEN' => UserFixtures::DEFAULT_API_TOKEN,
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
     * @param bool $json
     * @return string
     */
    protected function createUrl($url, $json = true)
    {
        return '/' . ltrim($url, '/') . ($json ? '.json' : '');
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $method
     */
    protected function assertRequestIsSecured(Client $client, string $url, $method = 'GET')
    {
        $this->request($client, $url, $method);
        $this->assertResponseIsSecured($client->getResponse(), $url);
    }

    /**
     * @param Response $response
     * @param string $url
     */
    protected function assertResponseIsSecured(Response $response, string $url)
    {
        $data = ['message' => 'Authentication required, missing headers: X-AUTH-USER, X-AUTH-TOKEN'];

        $this->assertEquals(
            $data,
            json_decode($response->getContent(), true),
            sprintf('The secure URL %s is not protected.', $url)
        );

        $this->assertEquals(
            Response::HTTP_FORBIDDEN, // TODO that should actually be Response::HTTP_UNAUTHORIZED
            $response->getStatusCode(),
            sprintf('The secure URL %s has the wrong status code %s.', $url, $response->getStatusCode())
        );
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

        $expected = [
            'code' => 403,
            'message' => 'Access denied.'
        ];

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            $expected,
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param string $content
     * @return Crawler
     */
    protected function request(Client $client, string $url, $method = 'GET', array $parameters = [], string $content = null)
    {
        $server = ['HTTP_CONTENT_TYPE' => 'application/json', 'CONTENT_TYPE' => 'application/json'];

        return $client->request($method, $this->createUrl($url), $parameters, [], $server, $content);
    }

    /**
     * @param string $role
     * @param string $url
     * @param string $method
     */
    protected function assertEntityNotFound(string $role, string $url, string $method = 'GET')
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, $url, $method);

        $expected = [
            'code' => 404,
            'message' => 'Not found'
        ];

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            $expected,
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    /**
     * @param string $role
     * @param string $url
     * @param array $data
     */
    protected function assertEntityNotFoundForPatch(string $role, string $url, array $data)
    {
        $client = $this->getClientForAuthenticatedUser($role);

        $this->request($client, $url, 'PATCH', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());

        $expected = [
            'code' => 404,
            'message' => 'Not found'
        ];

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            $expected,
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    /**
     * @param Response $response
     * @param string $message
     */
    protected function assertApiException(Response $response, string $message)
    {
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(['code' => 500, 'message' => $message], json_decode($response->getContent(), true));
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $message
     */
    protected function assertApiAccessDenied(Client $client, string $url, string $message)
    {
        $this->request($client, $url);
        $this->assertApiResponseAccessDenied($client->getResponse(), $message);
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $message
     */
    protected function assertApiResponseAccessDenied(Response $response, string $message)
    {
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $expected = ['code' => Response::HTTP_FORBIDDEN, 'message' => $message];
        $this->assertEquals($expected, json_decode($response->getContent(), true));
    }

    /**
     * @param Response $response
     * @param string[] $failedFields
     */
    protected function assertApiCallValidationError(Response $response, array $failedFields)
    {
        $this->assertFalse($response->isSuccessful());
        $result = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('children', $result['errors']);
        $data = $result['errors']['children'];

        foreach ($failedFields as $fieldName) {
            $this->assertArrayHasKey($fieldName, $data);
            $this->assertArrayHasKey('errors', $data[$fieldName]);
        }
    }
}
