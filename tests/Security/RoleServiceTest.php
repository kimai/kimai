<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Entity\Role;
use App\Tests\Mocks\Security\RoleServiceFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Security\RoleService
 */
class RoleServiceTest extends TestCase
{
    public function testWithEmptyRepository()
    {
        $real = [
            'ROLE_TEAMLEAD' => [0 => 'ROLE_USER'],
            'ROLE_ADMIN' => [0 => 'ROLE_TEAMLEAD'],
            'ROLE_SUPER_ADMIN' => [0 => 'ROLE_ADMIN']
        ];

        $sut = (new RoleServiceFactory($this))->create($real);

        $expected = ['ROLE_TEAMLEAD', 'ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

        self::assertEquals($expected, $sut->getAvailableNames());
        self::assertEquals($real, $sut->getSystemRoles());
    }

    public function testWithRepositoryData()
    {
        $real = [
            'ROLE_TEAMLEAD' => [0 => 'ROLE_USER'],
            'ROLE_ADMIN' => [0 => 'ROLE_TEAMLEAD'],
            'ROLE_SUPER_ADMIN' => [0 => 'ROLE_ADMIN']
        ];

        $repository = [
            (new Role())->setName('TEST_ROLE'),
            (new Role())->setName('ROLE_ADMIN'),
            (new Role())->setName('ROLE_ADMINX'),
            (new Role())->setName('TEST_ROLE'),
        ];

        $sut = (new RoleServiceFactory($this))->create($real, $repository);

        $expected = ['ROLE_TEAMLEAD', 'ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'TEST_ROLE', 'ROLE_ADMINX'];

        self::assertEquals($expected, $sut->getAvailableNames());
        self::assertEquals($real, $sut->getSystemRoles());
    }
}
