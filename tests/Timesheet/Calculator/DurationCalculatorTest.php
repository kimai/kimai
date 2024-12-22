<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\Calculator;

use App\Entity\Timesheet;
use App\Tests\Mocks\RoundingServiceFactory;
use App\Timesheet\Calculator\DurationCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\Calculator\DurationCalculator
 * @covers \App\Timesheet\RoundingService
 */
class DurationCalculatorTest extends TestCase
{
    public function testCalculateWithEmptyEnd(): void
    {
        $record = new Timesheet();
        $record->setBegin(new \DateTime());
        self::assertEquals(0, $record->getDuration());

        $sut = new DurationCalculator((new RoundingServiceFactory($this))->create());
        $sut->calculate($record, []);
        self::assertEquals(0, $record->getDuration());
    }

    /**
     * @dataProvider getTestData
     */
    public function testCalculate($rules, $start, $end, $expectedDuration): void
    {
        $record = new Timesheet();
        $record->setBegin($start);
        $record->setEnd($end);
        self::assertEquals(0, $record->getDuration());

        $sut = new DurationCalculator((new RoundingServiceFactory($this))->create($rules));
        $sut->calculate($record, []);
        self::assertEquals($expectedDuration, $record->getDuration());
    }

    public static function getTestData()
    {
        $start = new \DateTime();
        $start->setTime(12, 0, 0);
        $day = $start->format('l');

        return [
            [
                null,
                $start,
                (clone $start)->setTimestamp($start->getTimestamp() + 1837),
                1837
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 15,
                        'end' => 15,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                5400
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                4517
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 17, 35),
                (clone $start)->setTime(13, 32, 52),
                4560
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 30,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 10, 51),
                (clone $start)->setTime(14, 40, 52),
                10800
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 15,
                        'end' => 0,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                    'foo' => [
                        'days' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 0,
                        'end' => 1,
                        'duration' => 30,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 35), // 12:15
                (clone $start)->setTime(14, 32, 52), // 14:33 => 2:18 => 2:30
                9000
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 15,
                        'end' => 0,
                        'duration' => 30,
                        'mode' => 'default',
                    ],
                    'foo' => [
                        'days' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 0,
                        'end' => 1,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 35), // 12:15
                (clone $start)->setTime(14, 32, 52), // 14:33 => 2:18 (second duration will not be rounded)
                8280
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 1,
                        'mode' => 'default',
                    ],
                    'foo' => [
                        'days' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 1,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                0
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 1,
                        'mode' => 'default',
                    ],
                    'foo' => [
                        'days' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 1,
                        'end' => 1,
                        'duration' => 1,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 0), // no diff, to test ...
                (clone $start)->setTime(12, 27, 0), // ... that no rounding is applied
                0
            ],
            [
                [
                    'default' => [
                        'days' => $day,
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                    'foo' => [
                        'days' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                        'begin' => 0,
                        'end' => 0,
                        'duration' => 0,
                        'mode' => 'default',
                    ],
                ],
                (clone $start)->setTime(12, 27, 35), // no diff, to test ...
                (clone $start)->setTime(12, 27, 35), // ... that no rounding is applied
                0
            ],
        ];
    }
}
