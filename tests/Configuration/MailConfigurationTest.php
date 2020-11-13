<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\MailConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\MailConfiguration
 */
class MailConfigurationTest extends TestCase
{
    public function testGetFromAddress()
    {
        $sut = new MailConfiguration('foo-bar123@example.com');
        self::assertEquals('foo-bar123@example.com', $sut->getFromAddress());
    }

    public function testGetFromAddressWithEmptyAddressReturnsNull()
    {
        $sut = new MailConfiguration('');
        self::assertNull($sut->getFromAddress());
    }
}
