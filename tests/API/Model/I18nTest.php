<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Model;

use App\API\Model\I18n;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\API\Model\I18n
 */
class I18nTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new I18n();
        $this->assertTrue($sut->isIs24hours());
        $this->assertEquals('', $sut->getDuration());
        $this->assertEquals('', $sut->getDate());
        $this->assertEquals('', $sut->getDateTime());
        $this->assertEquals('', $sut->getFormDate());
        $this->assertEquals('', $sut->getFormDateTime());
        $this->assertEquals('', $sut->getTime());
    }

    public function testSetter()
    {
        $sut = new I18n();

        $this->assertInstanceOf(I18n::class, $sut->setIs24hours(false));
        $this->assertInstanceOf(I18n::class, $sut->setDuration('foo'));
        $this->assertInstanceOf(I18n::class, $sut->setDate('bar'));
        $this->assertInstanceOf(I18n::class, $sut->setDateTime('hello'));
        $this->assertInstanceOf(I18n::class, $sut->setFormDate('world'));
        $this->assertInstanceOf(I18n::class, $sut->setFormDateTime('testing'));
        $this->assertInstanceOf(I18n::class, $sut->setTime('fun'));

        $this->assertFalse($sut->isIs24hours());
        $this->assertEquals('foo', $sut->getDuration());
        $this->assertEquals('bar', $sut->getDate());
        $this->assertEquals('hello', $sut->getDateTime());
        $this->assertEquals('world', $sut->getFormDate());
        $this->assertEquals('testing', $sut->getFormDateTime());
        $this->assertEquals('fun', $sut->getTime());
    }
}
