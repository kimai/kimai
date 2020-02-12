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
        $previous = getenv('MAILER_FROM');
        putenv('MAILER_FROM=foo-bar123@example.com');

        $sut = new MailConfiguration();
        self::assertEquals('foo-bar123@example.com', $sut->getFromAddress());

        putenv('MAILER_FROM=');
        self::assertNull($sut->getFromAddress());

        putenv('MAILER_FROM=' . $previous);
    }
}
