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
use App\Tests\Plugin\Fixtures\TestPlugin;
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
        $sut = new PluginManager([]);

        $plugin = $this->getMockBuilder(PluginInterface::class)
            ->onlyMethods(['getName', 'getPath'])
            ->getMock();

        $plugin->method('getName')->willReturn('foo');
        $plugin->method('getPath')->willReturn('bar');

        $sut->addPlugin(new TestPlugin());
        $sut->addPlugin($plugin);
        $sut->addPlugin(new TestPlugin());

        // make sure a plugin with the same name is not added twice, the first one wins!
        $this->assertEquals(2, \count($sut->getPlugins()));

        $foo = $sut->getPlugin('foo');
        $this->assertInstanceOf(Plugin::class, $foo);
        $this->assertEquals('foo', $foo->getName());
        $this->assertEquals('bar', $foo->getPath());

        $test = $sut->getPlugin('TestPlugin');
        $this->assertInstanceOf(Plugin::class, $test);
        $this->assertEquals('TestPlugin', $test->getName());
        $this->assertEquals(new PluginMetadata(), $test->getMetadata());
    }

    public function testLoadMetadata()
    {
        $sut = new PluginManager([new TestPlugin()]);
        $plugin = $sut->getPlugin('TestPlugin');
        $sut->loadMetadata($plugin);

        $meta = $plugin->getMetadata();
        $this->assertEquals('0.9', $meta->getKimaiVersion());
        $this->assertEquals('1.0', $meta->getVersion());
        $this->assertEquals('TestPlugin', $plugin->getId());
        $this->assertEquals('TestPlugin from composer.json', $plugin->getName());
        $this->assertEquals('Just a test fixture for the PluginManager', $meta->getDescription());
        $this->assertEquals('https://github.com/kevinpapst/kimai2', $meta->getHomepage());
    }
}
