<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Plugin;

use App\Plugin\PluginMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PluginMetadata::class)]
class PluginMetadataTest extends TestCase
{
    public function testNonExistingDirectoryThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bundle does not ship composer.json, which is required since 2.0.');

        PluginMetadata::createFromPath(__DIR__);
    }
}
