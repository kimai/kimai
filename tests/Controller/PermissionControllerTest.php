<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

#[Group('integration')]
class PermissionControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/admin/permissions');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/permissions');
    }

    public function testPermissions(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/permissions');
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_user_admin_permissions', 136);
        $this->assertPageActions($client, [
            'create modal-ajax-form' => $this->createUrl('/admin/permissions/roles/create'),
        ]);

        $content = $client->getResponse()->getContent();
        $this->assertTableHeader($content);
    }

    private function assertTableHeader(string $content): void
    {
        // the english translation instead of the real system user role names
        self::assertStringContainsString('<th data-field="ROLE_USER" class="alwaysVisible text-center bg-green-lt col_ROLE_USER">', $content);
        self::assertStringContainsString('<th data-field="ROLE_TEAMLEAD" class="alwaysVisible text-center col_ROLE_TEAMLEAD">', $content);
        self::assertStringContainsString('<th data-field="ROLE_ADMIN" class="alwaysVisible text-center col_ROLE_ADMIN">', $content);
        self::assertStringContainsString('<th data-field="ROLE_SUPER_ADMIN" class="alwaysVisible text-center bg-orange-lt col_ROLE_SUPER_ADMIN">', $content);
    }

    public function testCreateRoleIsSecured(): void
    {
        $this->assertUrlIsSecured('/admin/permissions/roles/create');
    }

    public function testCreateRoleIsSecuredForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/permissions');
    }

    public function testCreateRole(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/permissions/roles/create');
        $form = $client->getCrawler()->filter('form[name=role]')->form();
        $client->submit($form, [
            'role' => [
                'name' => 'TEST_ROLE',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/permissions'));
        $client->followRedirect();

        $content = $client->getResponse()->getContent();
        $this->assertTableHeader($content);
    }

    public function testDeleteRoleIsSecuredForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/permissions');
    }

    public function testDeleteRole(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/permissions/roles/create');
        $form = $client->getCrawler()->filter('form[name=role]')->form();
        $client->submit($form, [
            'role' => [
                'name' => 'TEST_ROLE',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/permissions'));
        $client->followRedirect();

        $roles = $this->getEntityManager()->getRepository(Role::class)->findAll();
        $id = null;
        foreach ($roles as $role) {
            if ($role->getName() === 'TEST_ROLE') {
                $id = $role->getId();
                break;
            }
        }

        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('<th data-field="TEST_ROLE" class="alwaysVisible text-center col_TEST_ROLE">', $content);

        // add user to role
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/roles');
        $form = $client->getCrawler()->filter('form[name=user_roles]')->form();
        $client->submit($form, [
            'user_roles[roles]' => [
                0 => 'ROLE_TEAMLEAD',
                2 => 'ROLE_SUPER_ADMIN',
                3 => 'TEST_ROLE'
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/roles'));
        $client->followRedirect();
        self::assertTrue($client->getResponse()->isSuccessful());

        $user = $this->getUserByName(UserFixtures::USERNAME_USER);
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_SUPER_ADMIN', 'TEST_ROLE', 'ROLE_USER'], $user->getRoles());

        // deleting a role is an API call, the link only carries the target URL
        $this->request($client, '/admin/permissions');
        $node = $client->getCrawler()->filter('div.card .card-title a.api-link');
        self::assertEquals(1, $node->count());
        self::assertEquals('DELETE', $node->attr('data-method'));
        self::assertEquals('/api/users/roles/' . $id, $node->attr('data-href'));

        $deleteUrl = $node->attr('data-href');
        self::assertIsString($deleteUrl);
        $client->request('DELETE', $deleteUrl);
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $this->request($client, '/admin/permissions');
        $content = $client->getResponse()->getContent();
        self::assertStringNotContainsString('<th data-field="TEST_ROLE" class="alwaysVisible text-center">', $content);

        // verify that role was removed from user
        $user = $this->getUserByName(UserFixtures::USERNAME_USER);
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_SUPER_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testSavePermissionIsSecured(): void
    {
        $client = self::createClient();

        $role = new Role();
        $role->setName('TEST_ROLE');

        $em = $this->getEntityManager();
        $em->persist($role);
        $em->flush();

        $this->assertRequestIsSecured($client, '/admin/permissions/roles/' . $role->getId() . '/view_user', 'POST');
    }

    public function testSavePermissionIsSecuredForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/permissions');
    }

    private function requestPermissionSave(HttpKernelBrowser $client, string $url, string $token, bool $value = true): void
    {
        $this->request($client, $url, 'POST', [], json_encode(['token' => $token, 'value' => $value]) ?: '');
    }

    public function testSavePermissionWithoutTokenIsRejected(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/permissions');

        $role = new Role();
        $role->setName('TEST_ROLE');
        $em = $this->getEntityManager();
        $em->persist($role);
        $em->flush();

        $url = '/admin/permissions/roles/' . $role->getId() . '/view_user';

        // no body at all
        $this->request($client, $url, 'POST');
        self::assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());

        // a wrong token
        $this->requestPermissionSave($client, $url, 'asdfasdf');
        self::assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());

        // a valid token, but no value to apply
        $this->assertAccessIsGranted($client, '/admin/permissions');
        $validToken = $client->getCrawler()->filter('div#permission-token')->attr('data-value');
        self::assertIsString($validToken);
        $this->request($client, $url, 'POST', [], json_encode(['token' => $validToken]) ?: '');
        self::assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());

        $em->clear();
        self::assertCount(0, $em->getRepository(RolePermission::class)->findAll());
    }

    public function testSavePermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/permissions/roles/create');
        $form = $client->getCrawler()->filter('form[name=role]')->form();
        $client->submit($form, [
            'role' => [
                'name' => 'TEST_ROLE',
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/admin/permissions'));
        $client->followRedirect();

        $em = $this->getEntityManager();
        $rolePermissions = $em->getRepository(RolePermission::class)->findAll();
        self::assertEquals(0, \count($rolePermissions));

        $roles = $em->getRepository(Role::class)->findAll();
        $id = null;
        foreach ($roles as $role) {
            if ($role->getName() === 'TEST_ROLE') {
                $id = $role->getId();
                break;
            }
        }

        $token = $client->getCrawler()->filter('div#permission-token')->attr('data-value');
        self::assertIsString($token);

        $this->requestPermissionSave($client, '/admin/permissions/roles/' . $id . '/view_user', $token, true);
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertArrayHasKey('token', $result);

        $rolePermissions = $em->getRepository(RolePermission::class)->findAll();
        self::assertCount(1, $rolePermissions);
        $permission = $rolePermissions[0];
        self::assertInstanceOf(RolePermission::class, $permission);
        self::assertEquals('view_user', $permission->getPermission());
        self::assertTrue($permission->isAllowed());
        self::assertEquals('TEST_ROLE', $permission->getRole()->getName());
        self::assertEquals($id, $permission->getRole()->getId());

        // flush the cache to prevent wrong results
        $em->clear();

        // update the permission
        self::assertIsString($result['token']);
        $this->requestPermissionSave($client, '/admin/permissions/roles/' . $id . '/view_user', $result['token'], false);

        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertArrayHasKey('token', $result);

        $rolePermissions = $em->getRepository(RolePermission::class)->findAll();
        self::assertEquals(1, \count($rolePermissions));
        $permission = $rolePermissions[0];
        self::assertInstanceOf(RolePermission::class, $permission);
        self::assertEquals('view_user', $permission->getPermission());
        self::assertFalse($permission->isAllowed());
    }
}
