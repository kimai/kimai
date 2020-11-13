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
        $this->assertStringContainsString('<title>Kimai 2 - API Docs</title>', $client->getResponse()->getContent());
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
        $this->assertStringContainsString('"title":"Kimai 2 - API Docs"', $client->getResponse()->getContent());
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @param string $url
     * @return string
     */
    protected function createUrl($url)
    {
        return '/' . ltrim($url, '/');
    }
}
