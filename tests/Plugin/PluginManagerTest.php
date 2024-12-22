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
 * @covers \App\Plugin\Plugin
 * @covers \App\Plugin\PluginMetadata
 */
class PluginManagerTest extends TestCase
{
    public function testEmptyObject(): void
    {
        $sut = new PluginManager([]);
        self::assertEmpty($sut->getPlugins());
        self::assertNull($sut->getPlugin('foo'));
        self::assertFalse($sut->hasPlugin('foo'));
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
        self::assertEquals(2, \count($sut->getPlugins()));

        self::assertFalse($sut->hasPlugin('bar'));
        self::assertTrue($sut->hasPlugin('foo'));
        $foo = $sut->getPlugin('foo');
        self::assertInstanceOf(Plugin::class, $foo);
        self::assertEquals('foo', $foo->getId());
        self::assertEquals('bar', $foo->getPath());

        $test = $sut->getPlugin('TestPlugin');
        self::assertInstanceOf(Plugin::class, $test);
        self::assertEquals('TestPlugin', $test->getId());
        self::assertEquals('TestPlugin from composer.json', $test->getName());

        $meta = $test->getMetadata();
        self::assertEquals(10000, $meta->getKimaiVersion());
        self::assertEquals('1.0', $meta->getVersion());
        self::assertEquals('TestPlugin', $test->getId());
        self::assertEquals('TestPlugin from composer.json', $meta->getName());
        self::assertEquals('Just a test fixture for the PluginManager', $meta->getDescription());
        self::assertEquals('https://github.com/kimai/kimai', $meta->getHomepage());
    }
}
