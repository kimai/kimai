<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Ldap\LdapDriverException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LdapDriverException::class)]
class LdapDriverExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new LdapDriverException('Whooops');

        self::assertInstanceOf(\Exception::class, $sut);

        self::assertEquals('Whooops', $sut->getMessage());
    }
}
