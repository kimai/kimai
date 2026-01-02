<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use App\Tests\Controller\AbstractControllerBaseTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class ApiDocControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/doc');
    }

    public function testGetDocs(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/doc');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('<title>Kimai', $content);
        self::assertStringContainsString('docs.apiDescriptionDocument', $content);
        self::assertStringContainsString('const config = {"basePath":"/api/doc","router":"memory","logo":"/touch-icon-192x192.png","hideInternal":true};', $content);
        $results = preg_match('/docs\.apiDescriptionDocument\ \=\ (.*)\.spec;/', $content, $matches);
        self::assertNotFalse($results);
        $swaggerJson = json_decode($matches[1], true);
        self::assertIsArray($swaggerJson);
        self::assertArrayHasKey('spec', $swaggerJson);
        $json = $swaggerJson['spec'];
        self::assertArrayHasKey('paths', $json);

        $tags = [];
        foreach ($json['paths'] as $path) {
            foreach ($path as $method) {
                foreach ($method['tags'] as $tag) {
                    $tags[$tag] = $tag;
                }
            }
        }

        $expectedKeys = ['Actions', 'Activity', 'Default', 'Customer', 'Project', 'Tag', 'Team', 'Timesheet', 'User', 'Invoice', 'Export'];
        $actual = array_keys($tags);

        sort($actual);
        sort($expectedKeys);

        self::assertEquals($expectedKeys, $actual, \sprintf('Expected %s sections in API docs, but found %s.', \count($actual), \count($expectedKeys)));

        $paths = [
            '/api/actions/timesheet/{id}/{view}/{locale}',
            '/api/actions/activity/{id}/{view}/{locale}',
            '/api/actions/project/{id}/{view}/{locale}',
            '/api/actions/customer/{id}/{view}/{locale}',
            '/api/activities',
            '/api/activities/{id}',
            '/api/activities/{id}/meta',
            '/api/activities/{id}/rates',
            '/api/activities/{id}/rates/{rateId}',
            '/api/config/timesheet',
            '/api/config/colors',
            '/api/customers',
            '/api/customers/{id}',
            '/api/customers/{id}/meta',
            '/api/customers/{id}/rates',
            '/api/customers/{id}/rates/{rateId}',
            '/api/export/{id}',
            '/api/invoices',
            '/api/invoices/{id}',
            '/api/projects',
            '/api/projects/{id}',
            '/api/projects/{id}/meta',
            '/api/projects/{id}/rates',
            '/api/projects/{id}/rates/{rateId}',
            '/api/ping',
            '/api/version',
            '/api/plugins',
            '/api/tags',
            '/api/tags/find',
            '/api/tags/{id}',
            '/api/teams',
            '/api/teams/{id}',
            '/api/teams/{id}/members/{userId}',
            '/api/teams/{id}/customers/{customerId}',
            '/api/teams/{id}/projects/{projectId}',
            '/api/teams/{id}/activities/{activityId}',
            '/api/timesheets',
            '/api/timesheets/{id}',
            '/api/timesheets/recent',
            '/api/timesheets/active',
            '/api/timesheets/{id}/stop',
            '/api/timesheets/{id}/restart',
            '/api/timesheets/{id}/duplicate',
            '/api/timesheets/{id}/export',
            '/api/timesheets/{id}/meta',
            '/api/users',
            '/api/users/{id}',
            '/api/users/me',
            '/api/users/api-token/{id}',
            '/api/users/{id}/preferences',
        ];

        self::assertArrayHasKey('openapi', $json);
        self::assertEquals('3.0.0', $json['openapi']);
        self::assertArrayHasKey('info', $json);
        self::assertStringStartsWith('Kimai', $json['info']['title']);
        self::assertEquals('1.1', $json['info']['version']);

        self::assertArrayHasKey('paths', $json);
        self::assertEquals($paths, array_keys($json['paths']));

        self::assertArrayHasKey('security', $json);
        self::assertEquals(['bearer' => []], $json['security'][0]);

        self::assertArrayHasKey('components', $json);
        self::assertArrayHasKey('schemas', $json['components']);
        self::assertArrayHasKey('securitySchemes', $json['components']);
    }

    protected function createUrl(string $url): string
    {
        return '/' . ltrim($url, '/');
    }
}
