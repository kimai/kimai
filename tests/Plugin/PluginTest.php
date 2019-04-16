<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Plugin;

use App\Plugin\Plugin;
use App\Plugin\PluginMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Plugin\Plugin
 */
class PluginTest extends TestCase
{
    public function testEmptyObject()
    {
        $plugin = new Plugin();
        $this->assertNull($plugin->getName());
        $this->assertNull($plugin->getPath());
        $this->assertNull($plugin->getMetadata());
    }

    public function testGetterAndSetter()
    {
        $metadata = new PluginMetadata();
        $metadata
            ->setDescription('foo')
            ->setHomepage('http://www.example.com')
            ->setVersion('13.7')
            ->setKimaiVersion('1.1')
        ;

        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin->setName('foo'));
        $this->assertInstanceOf(Plugin::class, $plugin->setPath('bar'));
        $this->assertInstanceOf(Plugin::class, $plugin->setMetadata($metadata));

        $this->assertEquals('foo', $plugin->getName());
        $this->assertEquals('bar', $plugin->getPath());
        $this->assertSame($metadata, $plugin->getMetadata());
    }
}
