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
    public function testNonExistingDirectoryThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bundle "Plugin" does not ship composer.json, which is required since 2.0.');

        new PluginMetadata(__DIR__);
    }
}
