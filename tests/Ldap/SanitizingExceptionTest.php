<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Ldap\SanitizingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SanitizingException::class)]
class SanitizingExceptionTest extends TestCase
{
    public function testMessagesAreSanitized(): void
    {
        $ex = new \Exception('Could not find user foo with password bar in your LDAP');
        $sut = new SanitizingException($ex, 'bar');

        self::assertInstanceOf(\Exception::class, $sut);

        self::assertStringNotContainsString('bar', $sut->getMessage());
        self::assertStringNotContainsString('bar', (string) $sut);
        self::assertEquals('Could not find user foo with password **** in your LDAP', $sut->getMessage());
    }
}
