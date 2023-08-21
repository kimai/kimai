<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Model;

use App\WorkingTime\Model\DayAddon;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Model\DayAddon
 */
class DayAddonTest extends TestCase
{
    public function testDefaults(): void
    {
        $sut = new DayAddon('foo-bar', 7200, 0);
        self::assertEquals('foo-bar', $sut->getTitle());
        self::assertEquals(7200, $sut->getDuration());
        self::assertEquals(0, $sut->getVisibleDuration());
        self::assertTrue($sut->isBillable());

        $sut->setBillable(false);
        self::assertFalse($sut->isBillable());

        $sut->setBillable(true);
        self::assertTrue($sut->isBillable());
    }
}
