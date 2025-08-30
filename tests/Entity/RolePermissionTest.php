<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Role;
use App\Entity\RolePermission;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RolePermission::class)]
class RolePermissionTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new RolePermission();
        self::assertNull($sut->getId());
        self::assertNull($sut->getPermission());
        self::assertNull($sut->getRole());
        self::assertFalse($sut->isAllowed());
    }

    public function testSetterAndGetter(): void
    {
        $sut = new RolePermission();

        self::assertInstanceOf(RolePermission::class, $sut->setPermission('foo'));
        self::assertEquals('foo', $sut->getPermission());

        $role = (new Role())->setName('sdfsd');
        self::assertInstanceOf(RolePermission::class, $sut->setRole($role));
        self::assertSame($role, $sut->getRole());

        self::assertInstanceOf(RolePermission::class, $sut->setAllowed(true));
        self::assertTrue($sut->isAllowed());
    }
}
