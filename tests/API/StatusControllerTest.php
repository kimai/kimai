<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Constants;
use App\Entity\User;

/**
 * @group integration
 */
class StatusControllerTest extends APIControllerBaseTest
{
    public function testIsSecurePing(): void
    {
        $this->assertUrlIsSecured('/api/ping');
    }

    public function testIsSecureVersion(): void
    {
        $this->assertUrlIsSecured('/api/version');
    }

    public function testIsSecurePlugins(): void
    {
        $this->assertUrlIsSecured('/api/plugins');
    }

    public function testPing(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/ping');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertEquals(['message' => 'pong'], $result);
    }

    public function testVersion(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/version');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);

        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('versionId', $result);
        $this->assertArrayHasKey('copyright', $result);

        $this->assertSame(Constants::VERSION, $result['version']);
        $this->assertSame(Constants::VERSION_ID, $result['versionId']);
        $this->assertEquals(
            'Kimai ' . Constants::VERSION . ' by Kevin Papst.',
            $result['copyright']
        );
    }

    public function testPlugins(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/plugins');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        // no asserts, as plugins are disabled in tests
    }
}
