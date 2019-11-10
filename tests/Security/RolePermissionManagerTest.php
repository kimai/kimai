<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Repository\RolePermissionRepository;
use App\Security\RolePermissionManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Security\RolePermissionManager
 */
class RolePermissionManagerTest extends TestCase
{
    public function testWithEmptyRepository()
    {
        $repository = $this->getMockBuilder(RolePermissionRepository::class)->onlyMethods(['getAllAsArray'])->disableOriginalConstructor()->getMock();
        $repository->method('getAllAsArray')->willReturn([]);

        /** @var RolePermissionRepository $repository */
        $sut = new RolePermissionManager($repository, []);
        self::assertFalse($sut->isRegisteredPermission('foo'));
        self::assertEquals([], $sut->getPermissions());
        self::assertFalse($sut->hasPermission('TEST_ROLE', 'foo'));
    }

    public function testWithRepositoryData()
    {
        $repository = $this->getMockBuilder(RolePermissionRepository::class)->onlyMethods(['getAllAsArray'])->disableOriginalConstructor()->getMock();
        $repository->method('getAllAsArray')->willReturn([
            ['permission' => 'foo', 'role' => 'TEST_ROLE', 'allowed' => true],
            ['permission' => 'bar', 'role' => 'USER_ROLE', 'allowed' => true],
            ['permission' => 'foo', 'role' => 'USER_ROLE', 'allowed' => false],
        ]);

        /** @var RolePermissionRepository $repository */
        $sut = new RolePermissionManager($repository, []);

        // only data injected through the config will be registered as "known"
        self::assertFalse($sut->isRegisteredPermission('foo'));
        self::assertFalse($sut->isRegisteredPermission('bar'));
        self::assertEquals([], $sut->getPermissions());

        self::assertTrue($sut->hasPermission('TEST_ROLE', 'foo'));
        self::assertFalse($sut->hasPermission('USER_ROLE', 'foo'));
        self::assertTrue($sut->hasPermission('USER_ROLE', 'bar'));
    }

    public function testWithConfigData()
    {
        $repository = $this->getMockBuilder(RolePermissionRepository::class)->onlyMethods(['getAllAsArray'])->disableOriginalConstructor()->getMock();
        $repository->method('getAllAsArray')->willReturn([]);

        /** @var RolePermissionRepository $repository */
        $sut = new RolePermissionManager($repository, ['TEST_ROLE' => ['foo'], 'USER_ROLE' => ['bar']]);

        self::assertTrue($sut->isRegisteredPermission('foo'));
        self::assertTrue($sut->isRegisteredPermission('bar'));
        self::assertEquals(['foo', 'bar'], $sut->getPermissions());

        self::assertTrue($sut->hasPermission('TEST_ROLE', 'foo'));
        self::assertFalse($sut->hasPermission('TEST_ROLE', 'bar'));
        self::assertFalse($sut->hasPermission('USER_ROLE', 'foo'));
        self::assertTrue($sut->hasPermission('USER_ROLE', 'bar'));
    }

    public function testWithMixedData()
    {
        $repository = $this->getMockBuilder(RolePermissionRepository::class)->onlyMethods(['getAllAsArray'])->disableOriginalConstructor()->getMock();
        $repository->method('getAllAsArray')->willReturn([
            ['permission' => 'foo', 'role' => 'TEST_ROLE', 'allowed' => false],
            ['permission' => 'bar', 'role' => 'USER_ROLE', 'allowed' => true],
            ['permission' => 'foo', 'role' => 'USER_ROLE', 'allowed' => false],
            ['permission' => 'role_permissions', 'role' => 'ROLE_SUPER_ADMIN', 'allowed' => false],
            ['permission' => 'view_user', 'role' => 'ROLE_SUPER_ADMIN', 'allowed' => false],
            ['permission' => 'create_user', 'role' => 'ROLE_SUPER_ADMIN', 'allowed' => false],
        ]);

        /** @var RolePermissionRepository $repository */
        $sut = new RolePermissionManager($repository, [
            'ROLE_SUPER_ADMIN' => ['role_permissions', 'view_user', 'create_user'],
            'TEST_ROLE' => ['foo2', 'foo'],
            'USER_ROLE' => ['foo', 'bar']
        ]);

        self::assertTrue($sut->isRegisteredPermission('foo'));
        self::assertTrue($sut->isRegisteredPermission('bar'));
        self::assertEquals(['role_permissions', 'view_user', 'create_user', 'foo2', 'foo', 'bar'], array_values($sut->getPermissions()));

        self::assertTrue($sut->hasPermission('TEST_ROLE', 'foo2'));
        self::assertFalse($sut->hasPermission('TEST_ROLE', 'foo'));
        self::assertFalse($sut->hasPermission('USER_ROLE', 'foo'));
        self::assertTrue($sut->hasPermission('USER_ROLE', 'bar'));
        self::assertFalse($sut->hasPermission('ROLE_SUPER_ADMIN', 'create_user'));

        // the next two are a special case, which might never be falsified by the database
        self::assertTrue($sut->hasPermission('ROLE_SUPER_ADMIN', 'role_permissions'));
        self::assertTrue($sut->hasPermission('ROLE_SUPER_ADMIN', 'view_user'));
    }
}
