<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\Constants;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Constants
 */
class ConstantsTest extends TestCase
{
    public function testBuild()
    {
        $version = Constants::VERSION;
        $versionParts = explode('.', $version);

        $expectedId = $versionParts[0] * 10000 + $versionParts[1] * 100;
        if (isset($versionParts[2])) {
            $expectedId += (int) $versionParts[2];
        }

        self::assertEquals('1.14', Constants::VERSION, 'Invalid release number');
        self::assertEquals('dev', Constants::STATUS, 'Invalid status');
        self::assertEquals($expectedId, Constants::VERSION_ID, 'Invalid version ID');
    }
}
