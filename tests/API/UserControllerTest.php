<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class UserControllerTest extends APIControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/users');
    }

    public function getRoleTestData()
    {
        return [
            [User::ROLE_USER],
            [User::ROLE_TEAMLEAD],
            [User::ROLE_ADMIN],
        ];
    }

    /**
     * @dataProvider getRoleTestData
     */
    public function testIsSecureForRole(string $role)
    {
        $this->assertUrlIsSecuredForRole($role, '/api/users');
    }

    public function testGetCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(7, \count($result));
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
        $this->assertEquals(1, \count($result));
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
        $this->assertEquals(8, \count($result));
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

    public function testPostAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'avatar' => 'test123',
            'title' => 'asdfghjkl',
            'plainPassword' => 'foo@example.com',
            'enabled' => true,
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
            'roles' => [
                'ROLE_TEAMLEAD',
                'ROLE_ADMIN'
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertStructure($result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['username']);
        self::assertEquals('test123', $result['avatar']);
        self::assertEquals('asdfghjkl', $result['title']);
        self::assertTrue($result['enabled']);
        self::assertEquals('ru', $result['language']);
        self::assertEquals('Europe/Paris', $result['timezone']);
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_ADMIN'], $result['roles']);
    }

    public function testPostActionWithInvalidUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'plainPassword' => 'foo@example.com',
            'enabled' => true,
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
        ];
        $this->request($client, '/api/users', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('Access denied.', $json['message']);
    }

    public function testPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'avatar' => 'test123',
            'title' => 'asdfghjkl',
            'plainPassword' => 'foo@example.com',
            'enabled' => true,
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
            'roles' => [
                'ROLE_TEAMLEAD',
                'ROLE_ADMIN'
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        $data = [
            'avatar' => 'test321',
            'title' => 'qwertzui',
            'enabled' => false,
            'language' => 'it',
            'timezone' => 'America/New_York',
            'roles' => [
                'ROLE_TEAMLEAD',
            ],
        ];
        $this->request($client, '/api/users/' . $result['id'], 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertStructure($result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['username']);
        self::assertEquals('test321', $result['avatar']);
        self::assertEquals('qwertzui', $result['title']);
        self::assertFalse($result['enabled']);
        self::assertEquals('it', $result['language']);
        self::assertEquals('America/New_York', $result['timezone']);
        self::assertEquals(['ROLE_TEAMLEAD'], $result['roles']);
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
