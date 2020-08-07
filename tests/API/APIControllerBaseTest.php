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
use PHPUnit\Framework\Constraint\IsType;
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
     * @param bool $extraFields test for the error "This form should not contain extra fields"
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
            self::assertArrayHasKey($fieldName, $data, sprintf('Could not find validation error for field: %s', $fieldName));
            self::assertArrayHasKey('errors', $data[$fieldName], sprintf('Field %s has no validation problem', $fieldName));
        }

        $foundErrors = [];
        foreach ($data as $fieldName => $field) {
            if (\array_key_exists('errors', $field) && \count($field['errors']) > 0) {
                $foundErrors[$fieldName] = \count($field['errors']);
            }
        }

        self::assertEquals(\count($failedFields), \count($foundErrors), 'Expected and actual validation error amount differs');
    }

    protected static function getExpectedResponseStructure(string $type): array
    {
        switch ($type) {
            case 'TagEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'color' => 'string',
                ];

            // embedded meta data
            case 'CustomerMeta':
            case 'ProjectMeta':
            case 'ActivityMeta':
            case 'TimesheetMeta':
                return [
                    'name' => 'string',
                    'value' => 'string',
                ];

            // if a user is embedded in other objects
            case 'User':
            // if a list of users is loaded
            case 'UserCollection':
                return [
                    'id' => 'int',
                    'username' => 'string',
                    'enabled' => 'bool',
                    'alias' => '@string',
                ];

            // if a user is loaded explicitly
            case 'UserEntity':
                return [
                    'id' => 'int',
                    'username' => 'string',
                    'enabled' => 'bool',
                    'alias' => '@string',
                    'title' => '@string',
                    'avatar' => '@string',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                    'roles' => ['result' => 'array', 'type' => 'string'],
                    'language' => 'string',
                    'timezone' => 'string',
                ];

            // if a team is embedded
            case 'Team':
            // if a collection of teams is requested
            case 'TeamCollection':
                return [
                    'id' => 'int',
                    'name' => 'string',
                ];

            // explicitly requested team
            case 'TeamEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'teamlead' => ['result' => 'object', 'type' => 'User'],
                    'users' => ['result' => 'array', 'type' => 'User'],
                    'customers' => ['result' => 'array', 'type' => '@Customer'],
                    'projects' => ['result' => 'array', 'type' => '@Project'],
                    'activities' => ['result' => 'array', 'type' => '@Activity'],
                ];

            // if a customer is embedded in other objects
            case 'Customer':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'color' => '@string',
                ];

            // if a list of customers is loaded
            case 'CustomerCollection':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'boolean',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'CustomerMeta'],
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                    'currency' => 'string', // since 1.10
                ];

            // if a customer is loaded explicitly
            case 'CustomerEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'CustomerMeta'],
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                    'homepage' => '@string',
                    'number' => '@string',
                    'comment' => '@string',
                    'company' => '@string',
                    'contact' => '@string',
                    'address' => '@string',
                    'country' => 'string',
                    'currency' => 'string',
                    'phone' => '@string',
                    'fax' => '@string',
                    'mobile' => '@string',
                    'email' => '@string',
                    'timezone' => 'string',
                    'budget' => 'float',
                    'timeBudget' => 'int',
                    'vatId' => '@string', // since 1.10
                ];

            // if a project is embedded
            case 'Project':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'color' => '@string',
                    'customer' => 'int',
                ];

            // if a project is embedded in an expanded collection (here timesheet)
            case 'ProjectExpanded':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'color' => '@string',
                    'customer' => ['result' => 'object', 'type' => 'Customer'],
                ];

            // if a collection of projects is loaded
            case 'ProjectCollection':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'customer' => 'int',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'ProjectMeta'],
                    'parentTitle' => 'string',
                    'start' => '@datetime',
                    'end' => '@datetime',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                ];

            // if a project is explicitly loaded
            case 'ProjectEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'customer' => 'int',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'ProjectMeta'],
                    'parentTitle' => 'string',
                    'start' => '@datetime',
                    'end' => '@datetime',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                    'comment' => '@string',
                    'budget' => 'float',
                    'timeBudget' => 'int',
                    'orderNumber' => '@string',
                    'orderDate' => '@datetime',
                ];

            // embedded activities
            case 'Activity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'project' => '@int',
                    'color' => '@string',
                ];

            // collection of activities
            case 'ActivityCollection':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'project' => '@int',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'ProjectMeta'],
                    'parentTitle' => '@string',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                ];

            // if a activity is explicitly loaded
            case 'ActivityEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'project' => '@int',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'ProjectMeta'],
                    'parentTitle' => '@string',
                    'comment' => '@string',
                    'budget' => 'float',
                    'timeBudget' => 'int',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                ];

            case 'TimesheetEntity':
                return [
                    'id' => 'int',
                    'begin' => 'DateTime',
                    'end' => '@DateTime',
                    'duration' => '@int',
                    'description' => '@string',
                    'rate' => 'float',
                    'activity' => 'int',
                    'project' => 'int',
                    'tags' => ['result' => 'array', 'type' => 'string'],
                    'user' => 'int',
                    'metaFields' => ['result' => 'array', 'type' => 'TimesheetMeta'],
                    'internalRate' => 'float',
                    'exported' => 'bool',
                    'fixedRate' => '@float',
                    'hourlyRate' => '@float',
                    // TODO new fields: billable, category
                ];

            case 'TimesheetCollection':
                return [
                    'id' => 'int',
                    'begin' => 'DateTime',
                    'end' => '@DateTime',
                    'duration' => '@int',
                    'description' => '@string',
                    'rate' => 'float',
                    'activity' => 'int',
                    'project' => 'int',
                    'tags' => ['result' => 'array', 'type' => 'string'],
                    'user' => 'int',
                    'metaFields' => ['result' => 'array', 'type' => 'TimesheetMeta'],
                    'internalRate' => 'float',
                ];

            case 'TimesheetCollectionFull':
                return [
                    'id' => 'int',
                    'begin' => 'DateTime',
                    'end' => '@DateTime',
                    'duration' => '@int',
                    'description' => '@string',
                    'rate' => 'float',
                    'activity' => ['result' => 'object', 'type' => 'Activity'],
                    'project' => ['result' => 'object', 'type' => 'ProjectExpanded'],
                    'tags' => ['result' => 'array', 'type' => 'string'],
                    'user' => 'int',
                    'metaFields' => ['result' => 'array', 'type' => 'TimesheetMeta'],
                    'internalRate' => 'float',
                ];

            default:
                throw new \Exception(sprintf('Unknown API response type: %s', $type));
        }
    }

    /**
     * The $type is either one of the types configured in config/packages/nelmio_api_doc.yaml or the class name.
     *
     * @param string $type
     * @param array $result
     * @throws \Exception
     */
    protected function assertApiResponseTypeStructure(string $type, array $result)
    {
        $expected = self::getExpectedResponseStructure($type);
        $expectedKeys = array_keys($expected);

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        self::assertEquals($expectedKeys, $actual, sprintf('Structure for API response type "%s" does not match', $type));

        self::assertEquals(
            \count($actual),
            \count($expectedKeys),
            sprintf('Mismatch between expected and result keys for API response type "%s". Expected %s keys but found %s.', $type, \count($expected), \count($actual))
        );

        foreach ($expected as $key => $value) {
            if (\is_array($value)) {
                switch ($value['result']) {
                    case 'array':
                        foreach ($result[$key] as $subResult) {
                            if ($value['type'] === 'string') {
                                self::assertIsString($subResult);
                            } else {
                                self::assertIsArray($subResult);

                                if ($value['type'][0] === '@') {
                                    if (empty($result[$key])) {
                                        continue;
                                    }
                                    $value['type'] = substr($value['type'], 1);
                                }

                                self::assertApiResponseTypeStructure($value['type'], $subResult);
                            }
                        }
                        break;

                    case 'object':
                        self::assertIsArray($result[$key], sprintf('Key "%s" in type "%s" is not an array', $key, $type));

                        if ($value['type'][0] === '@') {
                            if (empty($result[$key])) {
                                break;
                            }
                            $value['type'] = substr($value['type'], 1);
                        }

                        self::assertApiResponseTypeStructure($value['type'], $result[$key]);
                        break;

                    default:
                        throw new \Exception(sprintf('Invalid result type "%s" for subresource given', $value['result']));
                }

                continue;
            }

            if ($value[0] === '@') {
                if (\is_null($result[$key])) {
                    continue;
                }
                $value = substr($value, 1);
            }

            if (strtolower($value) === 'datetime') {
                // TODO
                $value = 'string';
            }

            static::assertThat(
                $result[$key],
                new IsType($value),
                sprintf('Found type mismatch in structure for API response type %s. Expected type "%s" for key "%s".', $type, $value, $key)
            );
        }
    }
}
