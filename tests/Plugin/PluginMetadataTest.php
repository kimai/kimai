<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Plugin;

use App\Plugin\PluginMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Plugin\PluginMetadata
 */
class PluginMetadataTest extends TestCase
{
    public function testEmptyObject()
    {
        $sut = new PluginMetadata();
        $this->assertNull($sut->getDescription());
        $this->assertNull($sut->getHomepage());
        $this->assertNull($sut->getVersion());
        $this->assertNull($sut->getKimaiVersion());
    }

    public function testGetterAndSetter()
    {
        $sut = new PluginMetadata();
        $this->assertInstanceOf(PluginMetadata::class, $sut->setVersion('13.7'));
        $this->assertInstanceOf(PluginMetadata::class, $sut->setHomepage('http://www.example.com'));
        $this->assertInstanceOf(PluginMetadata::class, $sut->setDescription('foo bar'));
        $this->assertInstanceOf(PluginMetadata::class, $sut->setKimaiVersion('1.0'));

        $this->assertEquals('13.7', $sut->getVersion());
        $this->assertEquals('http://www.example.com', $sut->getHomepage());
        $this->assertEquals('foo bar', $sut->getDescription());
        $this->assertEquals('1.0', $sut->getKimaiVersion());
    }
}
