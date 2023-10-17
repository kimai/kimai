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
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/users');
    }

    /**
     * @return array<array<string>>
     */
    public function getRoleTestData(): array
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
    public function testIsSecureForRole(string $role): void
    {
        $this->assertUrlIsSecuredForRole($role, '/api/users');
    }

    public function testGetCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users');
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(7, \count($result));
        foreach ($result as $user) {
            self::assertApiResponseTypeStructure('UserCollection', $user);
        }
    }

    public function testGetCollectionFull(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users?full=true');

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(7, \count($result));
        foreach ($result as $user) {
            self::assertApiResponseTypeStructure('UserEntity', $user);
        }
    }

    public function testGetCollectionWithQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users', 'GET', ['visible' => 2, 'orderBy' => 'email', 'order' => 'DESC', 'term' => 'chris']);
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, \count($result));
        foreach ($result as $user) {
            self::assertApiResponseTypeStructure('UserCollection', $user);
        }
    }

    public function testGetCollectionWithQuery2(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users', 'GET', ['visible' => 3, 'orderBy' => 'email', 'order' => 'DESC']);
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(8, \count($result));
        foreach ($result as $user) {
            self::assertApiResponseTypeStructure('UserCollection', $user);
        }
    }

    public function testGetEntity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users/1');
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
        self::assertEquals('1', $result['id']);
        self::assertEquals('CFO', $result['title']);
        self::assertEquals('Clara Haynes', $result['alias']);
    }

    public function testGetMyProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/api/users/me');
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
        self::assertEquals('6', $result['id']);
        self::assertEquals('Super Administrator', $result['title']);
        self::assertEquals('', $result['alias']);
    }

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_SUPER_ADMIN, '/api/users/99');
    }

    public function testGetEntityAccessDenied(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertApiAccessDenied($client, '/api/users/4', 'You are not allowed to view this profile');
    }

    public function testGetEntityAccessAllowedForOwnProfile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/users/2');
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
    }

    public function testPostAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'title' => 'asdfghjkl',
            'plainPassword' => 'foo@example.com',
            'enabled' => true,
            'supervisor' => 2,
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
            'roles' => [
                'ROLE_TEAMLEAD',
                'ROLE_ADMIN'
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['username']);
        self::assertEquals('asdfghjkl', $result['title']);
        self::assertTrue($result['enabled']);
        self::assertEquals('ru', $result['language']);
        self::assertEquals('Europe/Paris', $result['timezone']);
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_ADMIN'], $result['roles']);
    }

    public function testPostActionWithShortPassword(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'title' => 'asdfghjkl',
            'plainPassword' => '1234567',
            'enabled' => true,
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
            'roles' => [
                'ROLE_TEAMLEAD',
                'ROLE_ADMIN'
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['plainPassword']);
    }

    public function testPostActionWithValidationErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => '',
            'email' => '',
            'plainPassword' => '123456',
            'language' => 'xx',
            'timezone' => 'XXX/YYY',
            'roles' => [
                'ABC',
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['username', 'email', 'plainPassword', 'language', 'timezone', 'roles']);
    }

    public function testPostActionWithInvalidUser(): void
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
        $this->assertApiResponseAccessDenied($response, 'Access denied.');
    }

    public function testPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => 'foo',
            'email' => 'foo@example.com',
            'title' => 'asdfghjkl',
            'plainPassword' => 'foo@example.com',
            'language' => 'ru',
            'timezone' => 'Europe/Paris',
            'roles' => [
                'ROLE_TEAMLEAD',
                'ROLE_ADMIN'
            ],
        ];
        $this->request($client, '/api/users', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);
        self::assertFalse($result['enabled']);

        $data = [
            'title' => 'qwertzui',
            'enabled' => true,
            'language' => 'it',
            'timezone' => 'America/New_York',
            'roles' => [
                'ROLE_TEAMLEAD',
            ],
        ];
        $this->request($client, '/api/users/' . $result['id'], 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $result = json_decode($content, true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('UserEntity', $result);
        $this->assertNotEmpty($result['id']);
        self::assertEquals('foo', $result['username']);
        self::assertEquals('qwertzui', $result['title']);
        self::assertTrue($result['enabled']);
        self::assertEquals('it', $result['language']);
        self::assertEquals('America/New_York', $result['timezone']);
        self::assertEquals(['ROLE_TEAMLEAD'], $result['roles']);
    }

    public function testPatchActionWithUnknownUser(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_SUPER_ADMIN, '/api/users/255', []);
    }

    public function testPatchActionWithInvalidUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/api/users/1', 'PATCH', [], json_encode(['language' => 'hu']));
        $this->assertApiResponseAccessDenied($client->getResponse(), 'Not allowed to edit user');
    }

    public function testPatchActionWithValidationErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $data = [
            'username' => '1',  // not existing in form
            'email' => '',
            'plainPassword' => '123456', // not existing in form
            'plainApiToken' => '123456', // not existing in form
            'language' => 'xx',
            'timezone' => 'XXX/YYY',
            'roles' => [
                'ABC',
            ],
        ];
        $this->request($client, '/api/users/1', 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['email', 'language', 'timezone', 'roles'], true);
    }
}
