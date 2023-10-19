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

    protected function createUrl(string $url): string
    {
        return '/' . ltrim($url, '/');
    }

    protected function assertPagination(Response $response, int $page, int $pageSize, int $totalPages, int $totalResults): void
    {
        $this->assertTrue($response->headers->has('X-Page'), 'Missing "X-Page" header');
        $this->assertTrue($response->headers->has('X-Total-Count'), 'Missing "X-Total-Count" header');
        $this->assertTrue($response->headers->has('X-Total-Pages'), 'Missing "X-Total-Pages" header');
        $this->assertTrue($response->headers->has('X-Per-Page'), 'Missing "X-Per-Page" header');

        $this->assertEquals($page, $response->headers->get('X-Page'));
        $this->assertEquals($totalResults, $response->headers->get('X-Total-Count'));
        $this->assertEquals($totalPages, $response->headers->get('X-Total-Pages'));
        $this->assertEquals($pageSize, $response->headers->get('X-Per-Page'));
    }

    protected function assertRequestIsSecured(HttpKernelBrowser $client, string $url, string $method = 'GET'): void
    {
        $this->request($client, $url, $method);
        $this->assertResponseIsSecured($client->getResponse(), $url);
    }

    /**
     * @param Response $response
     * @param string $url
     */
    protected function assertResponseIsSecured(Response $response, string $url): void
    {
        $data = ['message' => 'Authentication required, missing user header: X-AUTH-USER'];

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
    protected function assertUrlIsSecuredForRole(string $role, string $url, string $method = 'GET'): void
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $client->request($method, $this->createUrl($url));

        self::assertFalse(
            $client->getResponse()->isSuccessful(),
            sprintf('The secure URL %s is not protected for role %s', $url, $role)
        );

        $this->assertApiException($client->getResponse(), [
            'code' => Response::HTTP_FORBIDDEN,
            'message' => 'Forbidden'
        ]);
    }

    public function request(HttpKernelBrowser $client, string $url, string $method = 'GET', array $parameters = [], string $content = null): Crawler
    {
        $server = ['HTTP_CONTENT_TYPE' => 'application/json', 'CONTENT_TYPE' => 'application/json'];

        return $client->request($method, $this->createUrl($url), $parameters, [], $server, $content);
    }

    protected function assertEntityNotFound(string $role, string $url, string $method = 'GET', ?string $message = null): void
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, $url, $method);
        $this->assertApiException($client->getResponse(), [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found'
        ]);
    }

    protected function assertNotFoundForDelete(HttpKernelBrowser $client, string $url): void
    {
        $this->assertExceptionForMethod($client, $url, 'DELETE', [], [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found'
        ]);
    }

    protected function assertEntityNotFoundForPatch(string $role, string $url, array $data): void
    {
        $this->assertExceptionForPatchAction($role, $url, $data, [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found',
        ]);
    }

    protected function assertEntityNotFoundForPost(HttpKernelBrowser $client, string $url, array $data = []): void
    {
        $this->assertExceptionForMethod($client, $url, 'POST', $data, [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found',
        ]);
    }

    protected function assertExceptionForDeleteAction(string $role, string $url, array $data, array $expectedErrors): void
    {
        $this->assertExceptionForRole($role, $url, 'DELETE', $data, $expectedErrors);
    }

    protected function assertExceptionForPatchAction(string $role, string $url, array $data, array $expectedErrors): void
    {
        $this->assertExceptionForRole($role, $url, 'PATCH', $data, $expectedErrors);
    }

    protected function assertExceptionForPostAction(string $role, string $url, array $data, array $expectedErrors): void
    {
        $this->assertExceptionForRole($role, $url, 'POST', $data, $expectedErrors);
    }

    protected function assertExceptionForMethod(HttpKernelBrowser $client, string $url, string $method, array $data, array $expectedErrors): void
    {
        $this->request($client, $url, $method, [], json_encode($data));
        $this->assertApiException($client->getResponse(), $expectedErrors);
    }

    protected function assertApiException(Response $response, array $expectedErrors): void
    {
        self::assertFalse($response->isSuccessful());
        self::assertEquals($expectedErrors['code'], $response->getStatusCode());
        self::assertEquals($expectedErrors, json_decode($response->getContent(), true));
    }

    protected function assertExceptionForRole(string $role, string $url, string $method, array $data, array $expectedErrors): void
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->assertExceptionForMethod($client, $url, $method, $data, $expectedErrors);
    }

    protected function assertApi500Exception(Response $response, string $message): void
    {
        $this->assertApiException($response, ['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'message' => $message]);
    }

    protected function assertBadRequest(HttpKernelBrowser $client, string $url, string $method): void
    {
        $this->assertExceptionForMethod($client, $url, $method, [], [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    protected function assertApiAccessDenied(HttpKernelBrowser $client, string $url, string $message = 'Forbidden'): void
    {
        $this->request($client, $url);
        $this->assertApiResponseAccessDenied($client->getResponse(), $message);
    }

    protected function assertApiResponseAccessDenied(Response $response, string $message = 'Forbidden'): void
    {
        // APP_DEBUG = 1 means "real exception messages" - it is always overwritten
        $message = 'Forbidden';

        $this->assertApiException($response, [
            'code' => Response::HTTP_FORBIDDEN,
            'message' => $message
        ]);
    }

    /**
     * @param Response $response
     * @param array<int, string>|array<string, mixed> $failedFields
     * @param bool $extraFields test for the error "This form should not contain extra fields"
     * @param array<int, string>|array<string, mixed> $globalError
     */
    protected function assertApiCallValidationError(Response $response, array $failedFields, bool $extraFields = false, array $globalError = []): void
    {
        self::assertFalse($response->isSuccessful());
        $result = json_decode($response->getContent(), true);
        self::assertArrayHasKey('errors', $result);

        if ($extraFields) {
            self::assertArrayHasKey('errors', $result['errors']);
            self::assertEquals('This form should not contain extra fields.', $result['errors']['errors'][0]);
        }

        if (\count($globalError) > 0) {
            self::assertArrayHasKey('errors', $result['errors']);
            foreach ($globalError as $err) {
                self::assertTrue(\in_array($err, $result['errors']['errors']), 'Missing global validation error: ' . $err);
            }
        }

        self::assertArrayHasKey('children', $result['errors']);
        $data = $result['errors']['children'];

        $foundErrors = [];

        foreach ($failedFields as $key => $value) {
            $messages = [];
            $fieldName = $value;
            if (\is_string($key)) {
                $fieldName = $key;
                $messages = $value;
                if (!\is_array($messages)) {
                    $messages = [$value];
                }
            }

            while (stripos($fieldName, '.') !== false) {
                $parts = explode('.', $fieldName);
                $tmp = array_shift($parts);
                self::assertArrayHasKey($tmp, $data, sprintf('Could not find field "%s" in result', $tmp));
                $data = $data[$tmp];
                if (\count($data) === 1 && \array_key_exists('children', $data)) {
                    $data = $data['children'];
                }
                $fieldName = implode('.', $parts);
            }

            self::assertArrayHasKey($fieldName, $data, sprintf('Could not find validation error for field "%s" in list: %s', $fieldName, implode(', ', $failedFields)));
            self::assertArrayHasKey('errors', $data[$fieldName], sprintf('Field %s has no validation problem', $fieldName));
            foreach ($messages as $i => $message) {
                self::assertEquals($message, $data[$fieldName]['errors'][$i]);
            }
            if (\array_key_exists('errors', (array) $data[$fieldName]) && \count($data[$fieldName]['errors']) > 0) {
                $foundErrors[$fieldName] = \count($data[$fieldName]['errors']);
            }
        }

        self::assertEquals(\count($failedFields), \count($foundErrors), 'Expected and actual validation error amount differs');
    }

    protected static function getExpectedResponseStructure(string $type): array
    {
        switch ($type) {
            case 'PageActionItem':
                return [
                    'id' => 'string',
                    'title' => '@string',
                    'url' => '@string',
                    'class' => '@string',
                    'attr' => 'array',
                    'divider' => 'bool'
                ];

            case 'TagEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'color' => '@string',
                    'visible' => 'bool',
                ];

                // embedded meta data
            case 'UserPreference':
                return [
                    'name' => 'string',
                    'value' => '@string',
                ];

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
                    'apiToken' => 'bool',
                    'color' => '@string',
                    'alias' => '@string',
                    'accountNumber' => '@string',
                    'initials' => '@string',
                    'title' => '@string',
                ];

                // if a user is loaded explicitly
            case 'UserEntity':
                return [
                    'id' => 'int',
                    'username' => 'string',
                    'enabled' => 'bool',
                    'apiToken' => 'bool',
                    'alias' => '@string',
                    'title' => '@string',
                    'supervisor' => ['result' => 'object', 'type' => '@UserEntity'],
                    'avatar' => '@string',
                    'color' => '@string',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                    'roles' => ['result' => 'array', 'type' => 'string'],
                    'initials' => 'string',
                    'language' => 'string',
                    'timezone' => 'string',
                    'accountNumber' => '@string',
                    'memberships' => ['result' => 'array', 'type' => 'TeamMembership'],
                    'preferences' => ['result' => 'array', 'type' => 'UserPreference'],
                ];

                // if a team is embedded
            case 'Team':
                // if a collection of teams is requested
            case 'TeamCollection':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'color' => '@string',
                ];

                // explicitly requested team
            case 'TeamEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'color' => '@string',
                    'members' => ['result' => 'array', 'type' => 'TeamMember'],
                    'customers' => ['result' => 'array', 'type' => '@Customer'],
                    'projects' => ['result' => 'array', 'type' => '@Project'],
                    'activities' => ['result' => 'array', 'type' => '@Activity'],
                ];

                // if the team is used inside the team context
            case 'TeamMember':
                return [
                    'user' => ['result' => 'object', 'type' => 'User'],
                    'teamlead' => 'bool',
                ];

                // if the team is used inside the user context
            case 'TeamMembership':
                return [
                    'team' => ['result' => 'object', 'type' => 'Team'],
                    'teamlead' => 'bool',
                ];

                // if a customer is embedded in other objects
            case 'Customer':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'color' => '@string',
                    'number' => '@string',
                    'comment' => '@string',
                ];

                // if a list of customers is loaded
            case 'CustomerCollection':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'boolean',
                    'billable' => 'bool',
                    'color' => '@string',
                    'number' => '@string',
                    'comment' => '@string',
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
                    'billable' => 'bool',
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
                    'budgetType' => '@string', // since 1.15
                ];

                // if a project is embedded
            case 'Project':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'color' => '@string',
                    'customer' => 'int',
                    'globalActivities' => 'bool',
                    'comment' => '@string',
                ];

                // if a project is embedded in an expanded collection (here timesheet)
            case 'ProjectExpanded':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'color' => '@string',
                    'customer' => ['result' => 'object', 'type' => 'Customer'],
                    'globalActivities' => 'bool',
                    'comment' => '@string',
                ];

                // if a collection of projects is loaded
            case 'ProjectCollection':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'customer' => 'int',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'ProjectMeta'],
                    'parentTitle' => 'string',
                    'start' => '@datetime',
                    'end' => '@datetime',
                    'globalActivities' => 'bool',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                    'comment' => '@string',
                ];

                // if a project is explicitly loaded
            case 'ProjectEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'customer' => 'int',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'ProjectMeta'],
                    'parentTitle' => 'string',
                    'start' => '@date',
                    'end' => '@date',
                    'globalActivities' => 'bool',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                    'comment' => '@string',
                    'budget' => 'float',
                    'timeBudget' => 'int',
                    'orderNumber' => '@string',
                    'orderDate' => '@date',
                    'budgetType' => '@string', // since 1.15
                ];

                // embedded activities
            case 'Activity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'project' => '@int',
                    'color' => '@string',
                    'comment' => '@string',
                ];

            case 'ActivityExpanded':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'project' => ['result' => 'object', 'type' => '@ProjectExpanded'],
                    'color' => '@string',
                    'comment' => '@string',
                ];

                // collection of activities
            case 'ActivityCollection':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'project' => '@int',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'ProjectMeta'],
                    'parentTitle' => '@string',
                    'comment' => '@string',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                ];

                // if a activity is explicitly loaded
            case 'ActivityEntity':
                return [
                    'id' => 'int',
                    'name' => 'string',
                    'visible' => 'bool',
                    'billable' => 'bool',
                    'project' => '@int',
                    'color' => '@string',
                    'metaFields' => ['result' => 'array', 'type' => 'ProjectMeta'],
                    'parentTitle' => '@string',
                    'comment' => '@string',
                    'budget' => 'float',
                    'timeBudget' => 'int',
                    'teams' => ['result' => 'array', 'type' => 'Team'],
                    'budgetType' => '@string', // since 1.15
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
                    'billable' => 'bool',
                    'fixedRate' => '@float',
                    'hourlyRate' => '@float',
                    // TODO new fields: category
                ];

            case 'TimesheetExpanded':
                return [
                    'id' => 'int',
                    'begin' => 'DateTime',
                    'end' => '@DateTime',
                    'duration' => '@int',
                    'description' => '@string',
                    'rate' => 'float',
                    'activity' => ['result' => 'object', 'type' => 'ActivityExpanded'],
                    'project' => ['result' => 'object', 'type' => 'ProjectExpanded'],
                    'tags' => ['result' => 'array', 'type' => 'string'],
                    'user' => ['result' => 'object', 'type' => 'User'],
                    'metaFields' => ['result' => 'array', 'type' => 'TimesheetMeta'],
                    'internalRate' => 'float',
                    'exported' => 'bool',
                    'billable' => 'bool',
                    'fixedRate' => '@float',
                    'hourlyRate' => '@float',
                    // TODO new fields: category
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
                    'exported' => 'bool',
                    'billable' => 'bool',
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
                    'user' => ['result' => 'object', 'type' => 'User'],
                    'metaFields' => ['result' => 'array', 'type' => 'TimesheetMeta'],
                    'internalRate' => 'float',
                    'exported' => 'bool',
                    'billable' => 'bool',
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
    protected function assertApiResponseTypeStructure(string $type, array $result): void
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
                        if ($value['type'][0] === '@') {
                            if (empty($result[$key])) {
                                break;
                            }
                            $value['type'] = substr($value['type'], 1);
                        }

                        self::assertIsArray($result[$key], sprintf('Key "%s" in type "%s" is not an array', $key, $type));

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
                $date = \DateTime::createFromFormat('Y-m-d\TH:i:sO', $result[$key]);
                self::assertInstanceOf(\DateTime::class, $date, sprintf('Field "%s" was expected to be a Date with the format "Y-m-dTH:i:sO", but found: %s', $key, $result[$key]));
                $value = 'string';
            } elseif (strtolower($value) === 'date') {
                $date = \DateTime::createFromFormat('Y-m-d', $result[$key]);
                self::assertInstanceOf(\DateTime::class, $date, sprintf('Field "%s" was expected to be a Date with the format "Y-m-d", but found: %s', $key, $result[$key]));
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
