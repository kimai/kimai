<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Model;

use App\API\Model\Version;
use App\Constants;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\API\Model\Version
 */
class VersionTest extends TestCase
{
    public function testValues(): void
    {
        $sut = new Version();

        self::assertEquals(Constants::VERSION, $sut->version);
        self::assertEquals(Constants::VERSION_ID, $sut->versionId);
        self::assertEquals(Constants::SOFTWARE . ' ' . Constants::VERSION . ' by Kevin Papst.', $sut->copyright);
    }
}
