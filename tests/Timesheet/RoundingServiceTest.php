<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Entity\Timesheet;
use App\Tests\Mocks\RoundingServiceFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\RoundingService
 */
class RoundingServiceTest extends TestCase
{
    public function testCalculateWithEmptyEnd()
    {
        $record = new Timesheet();
        $record->setBegin(new \DateTime());
        $this->assertEquals(0, $record->getDuration());

        $sut = (new RoundingServiceFactory($this))->create();
        $sut->applyRoundings($record);
        $this->assertEquals(0, $record->getDuration());
    }

    /**
     * @dataProvider getTestData
     */
    public function testCalculate($rules, $start, $end, $expectedStart, $expectedEnd, $expectedDuration)
    {
        $record = new Timesheet();
        $record->setBegin($start);
        $record->setEnd($end);
        $this->assertEquals(0, $record->getDuration());

        $sut = (new RoundingServiceFactory($this))->create($rules);
        $sut->roundBegin($record);
        $this->assertEquals($expectedStart, $record->getBegin());
        $sut->roundEnd($record);
        $this->assertEquals($expectedEnd, $record->getEnd());

        // set the proper duration
        $record->setDuration($record->getEnd()->getTimestamp() - $record->getBegin()->getTimestamp());

        $sut->roundDuration($record);
        $this->assertEquals($expectedDuration, $record->getDuration());
    }

    public function getTestData()
    {
        $start = new \DateTime();
        $start->setTime(12, 0, 0);
        $day = $start->format('l');

        return [
            [
                null,
                $start,
                (clone $start)->setTimestamp($start->getTimestamp() + 1837),
                $start,
                (clone $start)->setTimestamp($start->getTimestamp() + 1837),
                1837
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 15,
                        'end' => 15,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                (clone $start)->setTime(12, 15, 00),
                (clone $start)->setTime(13, 45, 00),
                5400
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                4517
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                (clone $start)->setTime(12, 17, 00),
                (clone $start)->setTime(13, 33, 00),
                4560
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 30,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 10, 51),
                (clone $start)->setTime(14, 40, 52),
                (clone $start)->setTime(12, 10, 51),
                (clone $start)->setTime(14, 40, 52),
                10800
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 15,
                        'end' => 0,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                    'weekdays' => [
                        'weekdays' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 0,
                        'end' => 1,
                        'duration' => 30,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 35), // 12:15
                (clone $start)->setTime(14, 32, 52), // 14:33 => 2:18 => 2:30
                (clone $start)->setTime(12, 15, 00), // 12:15
                (clone $start)->setTime(14, 33, 00), // 14:33 => 2:18 => 2:30
                9000
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 15,
                        'end' => 0,
                        'duration' => 30,
                        'mode' => 'default',
                    ],
                    'weekdays' => [
                        'weekdays' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 0,
                        'end' => 1,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 35), // 12:15
                (clone $start)->setTime(14, 32, 52), // 14:33 => 2:18 (second duration will not be rounded)
                (clone $start)->setTime(12, 15, 00), // 12:15
                (clone $start)->setTime(14, 33, 00), // 14:33 => 2:18 (second duration will not be rounded)
                9000
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 1,
                        'mode' => 'default',
                    ],
                    'weekdays' => [
                        'weekdays' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 1,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                0
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 1,
                        'mode' => 'default',
                    ],
                    'weekdays' => [
                        'weekdays' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 1,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 00), // no diff, to test ...
                (clone $start)->setTime(12, 27, 00), // ... that no rounding is applied
                (clone $start)->setTime(12, 27, 00), // no diff, to test ...
                (clone $start)->setTime(12, 27, 00), // ... that no rounding is applied
                0
            ],
            [
                [
                    'default' => [
                        'weekdays' => $day,
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                    'weekdays' => [
                        'weekdays' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                0
            ],
        ];
    }
}
