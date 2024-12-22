<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\Rounding;

use App\Entity\Timesheet;
use App\Timesheet\Rounding\ClosestRounding;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\Rounding\ClosestRounding
 */
class ClosestRoundingTest extends TestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testCalculate($roundBegin, $roundEnd, $roundDuration, \DateTime $start, \DateTime $end, \DateTime $expectedStart, \DateTime $expectedEnd, $expectedDuration): void
    {
        $record = new Timesheet();
        $record->setBegin($start);
        $record->setEnd($end);
        self::assertEquals(0, $record->getDuration());

        $record->setDuration($record->getEnd()->getTimestamp() - $record->getBegin()->getTimestamp());

        $sut = new ClosestRounding();
        self::assertEquals('closest', $sut->getId());
        $sut->roundBegin($record, $roundBegin);
        $sut->roundEnd($record, $roundEnd);
        $record->setDuration($record->getEnd()->getTimestamp() - $record->getBegin()->getTimestamp());
        $sut->roundDuration($record, $roundDuration);

        self::assertEquals($expectedStart->getTimestamp(), $record->getBegin()->getTimestamp());
        self::assertEquals($expectedEnd->getTimestamp(), $record->getEnd()->getTimestamp());
        self::assertEquals($expectedDuration, $record->getDuration());
    }

    public static function getTestData()
    {
        $start = new \DateTime();
        $start->setTime(12, 0, 0);

        return [
            [
                0,
                0,
                0,
                $start,
                (clone $start)->setTimestamp($start->getTimestamp() + 1837),
                $start,
                (clone $start)->setTimestamp($start->getTimestamp() + 1837),
                1837
            ],
            [
                15,
                15,
                0,
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                (clone $start)->setTime(12, 15, 0),
                (clone $start)->setTime(13, 30, 0),
                4500
            ],
            [
                0,
                0,
                0,
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                4517
            ],
            [
                1,
                1,
                0,
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                (clone $start)->setTime(12, 18, 0),
                (clone $start)->setTime(13, 33, 0),
                4500
            ],
            [
                0,
                0,
                30,
                (clone $start)->setTime(12, 10, 51),
                (clone $start)->setTime(14, 40, 52),
                (clone $start)->setTime(12, 10, 51),
                (clone $start)->setTime(14, 40, 52),
                9000
            ],
            [
                0,
                1,
                30,
                (clone $start)->setTime(12, 27, 35), // 12:15
                (clone $start)->setTime(14, 32, 52), // 14:33 => 2:18 => 2:30
                (clone $start)->setTime(12, 27, 35),
                (clone $start)->setTime(14, 33, 0),
                7200
            ],
            [
                15,
                0,
                30,
                (clone $start)->setTime(12, 27, 35), // 12:15
                (clone $start)->setTime(14, 32, 52), // 14:33 => 2:18 (second duration will not be rounded)
                (clone $start)->setTime(12, 30, 0), // 12:15
                (clone $start)->setTime(14, 32, 52), // 14:33 => 2:18 (second duration will not be rounded)
                7200
            ],
            [
                0,
                0,
                1,
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                0
            ],
            [
                1,
                1,
                1,
                (clone $start)->setTime(12, 27, 0), // no diff, to test ...
                (clone $start)->setTime(12, 27, 0), // ... that no rounding is applied
                (clone $start)->setTime(12, 27, 0), // no diff, to test ...
                (clone $start)->setTime(12, 27, 0), // ... that no rounding is applied
                0
            ],
            [
                0,
                0,
                0,
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                0
            ],
        ];
    }
}
