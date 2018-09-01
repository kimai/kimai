<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;

/**
 * @coversDefaultClass \App\API\ActivityController
 * @group integration
 */
class ActivityControllerTest extends APIControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/activities');
    }

    public function testGetCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/activities');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, count($result));
        $this->assertStructure($result[0]);
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/activities/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertStructure($result);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/activities/2');
    }

    protected function assertStructure(array $result)
    {
        $expectedKeys = [
            'id', 'name', 'comment', 'visible', 'project_id'
        ];

        $actual = array_keys($result);

        $this->assertEquals(count($expectedKeys), count($actual), 'Activity entity has different amount of keys');
        $this->assertEquals($expectedKeys, $actual, 'Activity structure does not match');
    }
}
