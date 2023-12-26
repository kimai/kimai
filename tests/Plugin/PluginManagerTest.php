<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Plugin;

use App\Plugin\Plugin;
use App\Plugin\PluginInterface;
use App\Plugin\PluginManager;
use App\Plugin\PluginMetadata;
use App\Tests\Plugin\Fixtures\TestPlugin\TestPlugin;
use App\Tests\Plugin\Fixtures\TestPlugin2\TestPlugin2;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Plugin\PluginManager
 * @covers \App\Plugin\Plugin
 * @covers \App\Plugin\PluginMetadata
 */
class PluginManagerTest extends TestCase
{
    public function testEmptyObject(): void
    {
        $sut = new PluginManager([]);
        $this->assertEmpty($sut->getPlugins());
        $this->assertNull($sut->getPlugin('foo'));
        $this->assertFalse($sut->hasPlugin('foo'));
    }

    public function testAdd(): void
    {
        $plugin = $this->createMock(PluginInterface::class);
        $plugin->expects($this->any())->method('getName')->willReturn('foo');
        $plugin->expects($this->any())->method('getPath')->willReturn('bar');

        $plugins = [
            new TestPlugin(),
            $plugin,
            new TestPlugin2(),
            new TestPlugin(),
        ];

        $sut = new PluginManager($plugins);

        // make sure a plugin with the same name is not added twice, the first one wins!
        $this->assertEquals(2, \count($sut->getPlugins()));

        $this->assertFalse($sut->hasPlugin('bar'));
        $this->assertTrue($sut->hasPlugin('foo'));
        $foo = $sut->getPlugin('foo');
        $this->assertInstanceOf(Plugin::class, $foo);
        $this->assertEquals('foo', $foo->getId());
        $this->assertEquals('bar', $foo->getPath());

        $test = $sut->getPlugin('TestPlugin');
        $this->assertInstanceOf(Plugin::class, $test);
        $this->assertEquals('TestPlugin', $test->getId());
        $this->assertEquals('TestPlugin from composer.json', $test->getName());
        $this->assertInstanceOf(PluginMetadata::class, $test->getMetadata());

        $meta = $test->getMetadata();
        $this->assertEquals(10000, $meta->getKimaiVersion());
        $this->assertEquals('1.0', $meta->getVersion());
        $this->assertEquals('TestPlugin', $test->getId());
        $this->assertEquals('TestPlugin from composer.json', $meta->getName());
        $this->assertEquals('Just a test fixture for the PluginManager', $meta->getDescription());
        $this->assertEquals('https://github.com/kimai/kimai', $meta->getHomepage());
    }
}
