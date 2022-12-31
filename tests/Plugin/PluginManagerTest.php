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
use App\Tests\Plugin\Fixtures\TestPlugin\TestPlugin;
use App\Tests\Plugin\Fixtures\TestPlugin2\TestPlugin2;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Plugin\PluginManager
 */
class PluginManagerTest extends TestCase
{
    public function testEmptyObject()
    {
        $sut = new PluginManager([]);
        $this->assertEmpty($sut->getPlugins());
        $this->assertNull($sut->getPlugin('foo'));
    }

    public function testUnknownRendererReturnsNull()
    {
        $sut = new PluginManager([]);
        $this->assertNull($sut->getPlugin('foo'));
    }

    public function testAdd()
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

        $foo = $sut->getPlugin('foo');
        $this->assertInstanceOf(Plugin::class, $foo);
        $this->assertEquals('foo', $foo->getName());
        $this->assertEquals('bar', $foo->getPath());

        $test = $sut->getPlugin('TestPlugin');
        $this->assertInstanceOf(Plugin::class, $test);
        $this->assertEquals('TestPlugin', $test->getName());
        $this->assertNull($test->getMetadata());
    }

    public function testLoadMetadata()
    {
        $sut = new PluginManager([new TestPlugin()]);
        $plugin = $sut->getPlugin('TestPlugin');
        $sut->loadMetadata($plugin);

        $meta = $plugin->getMetadata();
        $this->assertEquals(10000, $meta->getKimaiVersion());
        $this->assertEquals('1.0', $meta->getVersion());
        $this->assertEquals('TestPlugin', $plugin->getId());
        $this->assertEquals('TestPlugin from composer.json', $meta->getName());
        $this->assertEquals('Just a test fixture for the PluginManager', $meta->getDescription());
        $this->assertEquals('https://github.com/kimai/kimai', $meta->getHomepage());
    }
}
