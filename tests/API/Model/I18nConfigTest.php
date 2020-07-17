<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Model;

use App\API\Model\I18nConfig;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\API\Model\I18nConfig
 */
class I18nConfigTest extends TestCase
{
    public function testSetter()
    {
        $sut = new I18nConfig();

        $this->assertInstanceOf(I18nConfig::class, $sut->setIs24hours(false));
        $this->assertInstanceOf(I18nConfig::class, $sut->setDuration('foo'));
        $this->assertInstanceOf(I18nConfig::class, $sut->setDate('bar'));
        $this->assertInstanceOf(I18nConfig::class, $sut->setDateTime('hello'));
        $this->assertInstanceOf(I18nConfig::class, $sut->setFormDate('world'));
        $this->assertInstanceOf(I18nConfig::class, $sut->setFormDateTime('testing'));
        $this->assertInstanceOf(I18nConfig::class, $sut->setTime('fun'));
    }
}
