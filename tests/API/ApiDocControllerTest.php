<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use App\Tests\Controller\ControllerBaseTest;

/**
 * @group integration
 */
class ApiDocControllerTest extends ControllerBaseTest
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/doc');
    }

    public function testGetDocs(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/doc');
        $this->assertStringContainsString('<title>Kimai - API Docs</title>', $client->getResponse()->getContent());
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

        $expectedKeys = ['Actions', 'Activity', 'Default', 'Customer', 'Project', 'Tag', 'Team', 'Timesheet', 'User'];
        $actual = array_keys($tags);

        sort($actual);
        sort($expectedKeys);

        self::assertEquals($expectedKeys, $actual, sprintf('Expected %s sections in API docs, but found %s.', \count($actual), \count($expectedKeys)));
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
            '/api/customers',
            '/api/customers/{id}',
            '/api/customers/{id}/meta',
            '/api/customers/{id}/rates',
            '/api/customers/{id}/rates/{rateId}',
            '/api/projects',
            '/api/projects/{id}',
            '/api/projects/{id}/meta',
            '/api/projects/{id}/rates',
            '/api/projects/{id}/rates/{rateId}',
            '/api/ping',
            '/api/version',
            '/api/plugins',
            '/api/tags',
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
        ];

        $this->assertArrayHasKey('openapi', $json);
        $this->assertEquals('3.0.0', $json['openapi']);
        $this->assertArrayHasKey('info', $json);
        $this->assertEquals('Kimai - API Docs', $json['info']['title']);
        $this->assertEquals('0.7', $json['info']['version']);

        $this->assertArrayHasKey('paths', $json);
        $this->assertEquals($paths, array_keys($json['paths']));

        $this->assertArrayHasKey('security', $json);
        $this->assertArrayHasKey('X-AUTH-USER', $json['security'][0]);
        $this->assertArrayHasKey('X-AUTH-TOKEN', $json['security'][0]);

        $this->assertArrayHasKey('components', $json);
        $this->assertArrayHasKey('schemas', $json['components']);
        $this->assertArrayHasKey('securitySchemes', $json['components']);

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    protected function createUrl(string $url): string
    {
        return '/' . ltrim($url, '/');
    }
}
