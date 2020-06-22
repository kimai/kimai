<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Ldap\LdapDriverException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Ldap\LdapDriverException
 */
class LdapDriverExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $sut = new LdapDriverException('Whooops');

        self::assertInstanceOf(\Exception::class, $sut);

        self::assertEquals('Whooops', $sut->getMessage());
    }
}
