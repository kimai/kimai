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

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(7, count($result));
        foreach ($result as $user) {
            $this->assertStructure($user, false);
        }
    }

    public function testGetCollectionWithQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users', 'GET', ['visible' => 2, 'orderBy' => 'email', 'order' => 'DESC']);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, count($result));
        foreach ($result as $user) {
            $this->assertStructure($user, false);
        }
    }

    public function testGetCollectionWithQuery2()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users', 'GET', ['visible' => 3, 'orderBy' => 'email', 'order' => 'DESC']);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(8, count($result));
        foreach ($result as $user) {
            $this->assertStructure($user, false);
        }
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertStructure($result);
        self::assertEquals('1', $result['id']);
        self::assertEquals('CFO', $result['title']);
        self::assertEquals('Clara Haynes', $result['alias']);
    }

    public function testGetMyProfile()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users/me');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertStructure($result);
        self::assertEquals('6', $result['id']);
        self::assertEquals('Super Administrator', $result['title']);
        self::assertEquals('', $result['alias']);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_SUPER_ADMIN, '/api/users/99');
    }

    public function testGetEntityAccessDenied()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertApiAccessDenied($client, '/api/users/4', 'You are not allowed to view this profile');
    }

    public function testGetEntityAccessAllowedForOwnProfile()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/users/2');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertStructure($result);
    }

    protected function assertStructure(array $result, $full = true)
    {
        $expectedKeys = ['id', 'username', 'enabled', 'alias'];

        if ($full) {
            $expectedKeys = array_merge(
                $expectedKeys,
                ['title', 'avatar', 'teams', 'roles', 'language', 'timezone']
            );
        }

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'User structure does not match');
    }
}
