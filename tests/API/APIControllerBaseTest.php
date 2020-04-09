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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * Adds some useful functions for writing API integration tests.
 */
abstract class APIControllerBaseTest extends ControllerBaseTest
{
    protected function getClientForAuthenticatedUser(string $role = User::ROLE_USER): HttpKernelBrowser
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
                throw new \Exception(sprintf('Unknown role "%s"', $role));
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

    protected function assertRequestIsSecured(HttpKernelBrowser $client, string $url, $method = 'GET')
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

        self::assertEquals(
            $data,
            json_decode($response->getContent(), true),
            sprintf('The secure URL %s is not protected.', $url)
        );

        self::assertEquals(
            Response::HTTP_FORBIDDEN,
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

        self::assertFalse(
            $client->getResponse()->isSuccessful(),
            sprintf('The secure URL %s is not protected for role %s', $url, $role)
        );

        $expected = [
            'code' => 403,
            'message' => 'Access denied.'
        ];

        self::assertEquals(403, $client->getResponse()->getStatusCode());

        self::assertEquals(
            $expected,
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    protected function request(HttpKernelBrowser $client, string $url, $method = 'GET', array $parameters = [], string $content = null): Crawler
    {
        $server = ['HTTP_CONTENT_TYPE' => 'application/json', 'CONTENT_TYPE' => 'application/json'];

        return $client->request($method, $this->createUrl($url), $parameters, [], $server, $content);
    }

    protected function assertEntityNotFound(string $role, string $url, string $method = 'GET')
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, $url, $method);

        $expected = [
            'code' => 404,
            'message' => 'Not found'
        ];

        self::assertEquals(404, $client->getResponse()->getStatusCode());

        self::assertEquals(
            $expected,
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    protected function assertNotFoundForDelete(HttpKernelBrowser $client, string $url)
    {
        return $this->assertExceptionForMethod($client, $url, 'DELETE', [], [
            'code' => 404,
            'message' => 'Not found'
        ]);
    }

    protected function assertEntityNotFoundForDelete(string $role, string $url)
    {
        return $this->assertExceptionForDeleteAction($role, $url, [], [
            'code' => 404,
            'message' => 'Not found'
        ]);
    }

    protected function assertEntityNotFoundForPatch(string $role, string $url, array $data)
    {
        return $this->assertExceptionForPatchAction($role, $url, $data, [
            'code' => 404,
            'message' => 'Not found'
        ]);
    }

    protected function assertEntityNotFoundForPost(string $role, string $url, array $data, ?string $message = null)
    {
        return $this->assertExceptionForPostAction($role, $url, $data, [
            'code' => 404,
            'message' => $message ?? 'Not found'
        ]);
    }

    protected function assertExceptionForDeleteAction(string $role, string $url, array $data, array $expectedErrors)
    {
        $this->assertExceptionForRole($role, $url, 'DELETE', $data, $expectedErrors);
    }

    protected function assertExceptionForPatchAction(string $role, string $url, array $data, array $expectedErrors)
    {
        $this->assertExceptionForRole($role, $url, 'PATCH', $data, $expectedErrors);
    }

    protected function assertExceptionForPostAction(string $role, string $url, array $data, array $expectedErrors)
    {
        $this->assertExceptionForRole($role, $url, 'POST', $data, $expectedErrors);
    }

    protected function assertExceptionForMethod(HttpKernelBrowser $client, string $url, string $method, array $data, array $expectedErrors)
    {
        $this->request($client, $url, $method, [], json_encode($data));
        $response = $client->getResponse();
        self::assertFalse($response->isSuccessful());

        self::assertEquals($expectedErrors['code'], $client->getResponse()->getStatusCode());

        self::assertEquals(
            $expectedErrors,
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    protected function assertExceptionForRole(string $role, string $url, string $method, array $data, array $expectedErrors)
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->assertExceptionForMethod($client, $url, $method, $data, $expectedErrors);
    }

    protected function assertApiException(Response $response, string $message)
    {
        self::assertFalse($response->isSuccessful());
        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals(['code' => 500, 'message' => $message], json_decode($response->getContent(), true));
    }

    protected function assertApiAccessDenied(HttpKernelBrowser $client, string $url, string $message)
    {
        $this->request($client, $url);
        $this->assertApiResponseAccessDenied($client->getResponse(), $message);
    }

    protected function assertApiResponseAccessDenied(Response $response, string $message)
    {
        self::assertFalse($response->isSuccessful());
        self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $expected = ['code' => Response::HTTP_FORBIDDEN, 'message' => $message];
        self::assertEquals($expected, json_decode($response->getContent(), true));
    }

    /**
     * @param Response $response
     * @param string[] $failedFields
     * @param bool $extraFields
     */
    protected function assertApiCallValidationError(Response $response, array $failedFields, bool $extraFields = false)
    {
        self::assertFalse($response->isSuccessful());
        $result = json_decode($response->getContent(), true);

        self::assertArrayHasKey('errors', $result);

        if ($extraFields) {
            self::assertArrayHasKey('errors', $result['errors']);
            self::assertEquals($result['errors']['errors'][0], 'This form should not contain extra fields.');
        }

        self::assertArrayHasKey('children', $result['errors']);
        $data = $result['errors']['children'];

        foreach ($failedFields as $fieldName) {
            self::assertArrayHasKey($fieldName, $data);
            self::assertArrayHasKey('errors', $data[$fieldName]);
        }
    }
}
