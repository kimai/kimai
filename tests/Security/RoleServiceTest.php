<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Security\RoleService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Security\RoleService
 */
class RoleServiceTest extends TestCase
{
    public function testGetAvailableNames()
    {
        $real = [
            'ROLE_TEAMLEAD' => [0 => 'ROLE_USER'],
            'ROLE_ADMIN' => [0 => 'ROLE_TEAMLEAD'],
            'ROLE_SUPER_ADMIN' => [0 => 'ROLE_ADMIN']
        ];

        $sut = new RoleService($real);

        $expected = ['ROLE_TEAMLEAD', 'ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

        self::assertEquals($expected, $sut->getAvailableNames());
    }
}
