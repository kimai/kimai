<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Ldap\FormLoginLdapFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Ldap\FormLoginLdapFactory
 */
class FormLoginLdapFactoryTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new FormLoginLdapFactory();

        self::assertEquals('kimai_ldap', $sut->getKey());
        self::assertEquals(-20, $sut->getPriority());
    }
}
