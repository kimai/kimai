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

/**
 * @group integration
 */
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
        self::assertStringContainsString('<title>Kimai', $client->getResponse()->getContent());
        $result = $client->getCrawler()->filter('script#swagger-data');
        $swaggerJson = json_decode($result->text(), true);
        $tags = [];
        foreach ($swaggerJson['spec']['paths'] as $path) {
            foreach ($path as $method) {
                foreach ($method['tags'] as $tag) {
                    $tags[$tag] = $tag;
                }
            }
        }

        $expectedKeys = ['Actions', 'Activity', 'Default', 'Customer', 'Project', 'Tag', 'Team', 'Timesheet', 'User', 'Invoice'];
        $actual = array_keys($tags);

        sort($actual);
        sort($expectedKeys);

        self::assertEquals($expectedKeys, $actual, \sprintf('Expected %s sections in API docs, but found %s.', \count($actual), \count($expectedKeys)));
    }

    public function testGetJsonDocs(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/doc.json');
        $json = json_decode($client->getResponse()->getContent(), true);

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
        ];

        self::assertArrayHasKey('openapi', $json);
        self::assertEquals('3.0.0', $json['openapi']);
        self::assertArrayHasKey('info', $json);
        self::assertStringStartsWith('Kimai', $json['info']['title']);
        self::assertEquals('1.0', $json['info']['version']);

        self::assertArrayHasKey('paths', $json);
        self::assertEquals($paths, array_keys($json['paths']));

        self::assertArrayHasKey('security', $json);
        self::assertEquals(['bearer' => []], $json['security'][0]);

        self::assertArrayHasKey('components', $json);
        self::assertArrayHasKey('schemas', $json['components']);
        self::assertArrayHasKey('securitySchemes', $json['components']);

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertNotEmpty($result);
    }

    protected function createUrl(string $url): string
    {
        return '/' . ltrim($url, '/');
    }
}
