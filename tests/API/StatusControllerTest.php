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
    public function testIsSecurePing()
    {
        $this->assertUrlIsSecured('/api/ping');
    }

    public function testIsSecureVersion()
    {
        $this->assertUrlIsSecured('/api/version');
    }

    public function testPing()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/ping');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertEquals(['message' => 'pong'], $result);
    }

    public function testVersion()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/version');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);

        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('candidate', $result);
        $this->assertArrayHasKey('semver', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('copyright', $result);

        $this->assertSame(Constants::VERSION, $result['version']);
        $this->assertEquals(Constants::STATUS, $result['candidate']);
        $this->assertEquals(Constants::VERSION . '-' . Constants::STATUS, $result['semver']);
        $this->assertEquals(Constants::NAME, $result['name']);
        $this->assertEquals(
            'Kimai 2 - ' . Constants::VERSION . ' ' . Constants::STATUS . ' (' . Constants::NAME . ') by Kevin Papst and contributors.',
            $result['copyright']
        );
    }
}
