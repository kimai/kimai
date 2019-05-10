<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Configuration;

/**
 * @covers \App\Entity\Configuration
 */
class ConfigurationTest extends AbstractEntityTest
{
    public function testDefaultValues()
    {
        $sut = new Configuration();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getValue());
    }

    public function testSetterAndGetter()
    {
        $sut = new Configuration();
        $this->assertInstanceOf(Configuration::class, $sut->setName('foo-bar'));
        $this->assertEquals('foo-bar', $sut->getName());
        $this->assertInstanceOf(Configuration::class, $sut->setValue('hello world'));
        $this->assertEquals('hello world', $sut->getValue());
    }
}
