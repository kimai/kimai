<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Configuration
 */
class ConfigurationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new Configuration();
        self::assertNull($sut->getId());
        self::assertNull($sut->getName());
        self::assertNull($sut->getValue());
    }

    public function testSetterAndGetter(): void
    {
        $sut = new Configuration();
        self::assertInstanceOf(Configuration::class, $sut->setName('foo-bar'));
        self::assertEquals('foo-bar', $sut->getName());
        self::assertInstanceOf(Configuration::class, $sut->setValue('hello world'));
        self::assertEquals('hello world', $sut->getValue());
    }
}
