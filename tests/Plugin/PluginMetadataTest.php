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
}
