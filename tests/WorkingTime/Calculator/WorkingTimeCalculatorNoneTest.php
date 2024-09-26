<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Calculator;

use App\WorkingTime\Calculator\WorkingTimeCalculatorNone;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Calculator\WorkingTimeCalculatorNone
 */
class WorkingTimeCalculatorNoneTest extends TestCase
{
    public function testDefaults(): void
    {
        $date = new \DateTime();
        $sut = new WorkingTimeCalculatorNone();
        self::assertTrue($sut->isWorkDay($date));
        self::assertEquals(0, $sut->getWorkHoursForDay($date));
    }
}
