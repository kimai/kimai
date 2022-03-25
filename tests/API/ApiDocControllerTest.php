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
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/doc');
    }

    public function testGetDocs()
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

        $expectedKeys = ['Activity', 'Default', 'Customer', 'Project', 'Tag', 'Team', 'Timesheet', 'User'];
        $actual = array_keys($tags);

        sort($actual);
        sort($expectedKeys);

        self::assertEquals($expectedKeys, $actual, sprintf('Expected %s sections in API docs, but found %s.', \count($actual), \count($expectedKeys)));
    }

    public function testGetJsonDocs()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/doc.json');
        $json = json_decode($client->getResponse()->getContent(), true);

        $paths = [
            0 => '/api/activities',
            1 => '/api/activities/{id}',
            2 => '/api/activities/{id}/meta',
            3 => '/api/activities/{id}/rates',
            4 => '/api/activities/{id}/rates/{rateId}',
            5 => '/api/config/i18n',
            6 => '/api/config/timesheet',
            7 => '/api/customers',
            8 => '/api/customers/{id}',
            9 => '/api/customers/{id}/meta',
            10 => '/api/customers/{id}/rates',
            11 => '/api/customers/{id}/rates/{rateId}',
            12 => '/api/projects',
            13 => '/api/projects/{id}',
            14 => '/api/projects/{id}/meta',
            15 => '/api/projects/{id}/rates',
            16 => '/api/projects/{id}/rates/{rateId}',
            17 => '/api/ping',
            18 => '/api/version',
            19 => '/api/plugins',
            20 => '/api/tags',
            21 => '/api/tags/{id}',
            22 => '/api/teams',
            23 => '/api/teams/{id}',
            24 => '/api/teams/{id}/members/{userId}',
            25 => '/api/teams/{id}/customers/{customerId}',
            26 => '/api/teams/{id}/projects/{projectId}',
            27 => '/api/teams/{id}/activities/{activityId}',
            28 => '/api/timesheets',
            29 => '/api/timesheets/{id}',
            30 => '/api/timesheets/recent',
            31 => '/api/timesheets/active',
            32 => '/api/timesheets/{id}/stop',
            33 => '/api/timesheets/{id}/restart',
            34 => '/api/timesheets/{id}/duplicate',
            35 => '/api/timesheets/{id}/export',
            36 => '/api/timesheets/{id}/meta',
            37 => '/api/users',
            38 => '/api/users/{id}',
            39 => '/api/users/me',
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
        $this->assertArrayHasKey('X-AUTH-TOKEN', $json['security'][1]);

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
