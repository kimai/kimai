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
 * @coversDefaultClass \App\API\HealthcheckController
 * @group integration
 */
class HealthcheckControllerTest extends APIControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/ping');
    }

    public function testPing()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/ping');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertEquals(['message' => 'pong'], $result);
    }

    public function testVersion()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/version');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);

        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('copyright', $result);

        $this->assertEquals(Constants::VERSION, $result['version']);
        $this->assertEquals(Constants::STATUS, $result['status']);
        $this->assertEquals(Constants::NAME, $result['name']);
        $this->assertEquals(
            'Kimai 2 - ' . Constants::VERSION . ' ' . Constants::STATUS . ' (' . Constants::NAME . ') by Kevin Papst and contributors.',
            $result['copyright']
        );
    }
}
