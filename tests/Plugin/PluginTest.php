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
use App\Plugin\PluginMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Plugin\Plugin
 */
class PluginTest extends TestCase
{
    public function testEmptyObject()
    {
        $plugin = new Plugin($this->createMock(PluginInterface::class));
        $this->assertEquals('', $plugin->getId());
        $this->assertEquals('', $plugin->getName());
        $this->assertEquals('', $plugin->getPath());
        $this->assertNull($plugin->getMetadata());
    }

    public function testGetterAndSetter()
    {
        $metadata = new PluginMetadata();

        $plugin = new Plugin($this->createMock(PluginInterface::class));
        $plugin->setMetadata($metadata);

        $this->assertEquals($metadata, $plugin->getMetadata());
    }
}
