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
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Plugin\Plugin
 */
class PluginTest extends TestCase
{
    public function testEmptyObject(): void
    {
        $plugin = new Plugin($this->createMock(PluginInterface::class));
        self::assertEquals('', $plugin->getId());
        self::assertEquals('', $plugin->getPath());
    }
}
