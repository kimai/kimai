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
 * @coversDefaultClass \App\API\UserController
 * @group integration
 */
class UserControllerTest extends APIControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/users');
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/api/users');
    }

    public function testGetCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertEquals(6, count($result));
        $this->assertStructure($result[0], false);
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertStructure($result);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_SUPER_ADMIN, '/api/users/99');
    }

    protected function assertStructure(array $result, $full = true)
    {
        $expectedKeys = ['id', 'username', 'enabled', 'alias'];

        if ($full) {
            $expectedKeys = ['id', 'username', 'enabled', 'roles', 'alias', 'title', 'avatar'];
        }

        $actual = array_keys($result);

        $this->assertEquals($expectedKeys, $actual, 'User structure does not match');
    }
}
