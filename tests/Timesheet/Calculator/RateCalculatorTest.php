<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\Calculator;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Timesheet\Calculator\RateCalculator;
use \PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\Calculator\RateCalculator
 */
class RateCalculatorTest extends TestCase
{
    const HOURLY_RATE = 75;

    protected function getTestUser()
    {
        $pref = new UserPreference();
        $pref->setName(UserPreference::HOURLY_RATE);
        $pref->setValue(self::HOURLY_RATE);

        $user = new User();
        $user->setPreferences([$pref]);

        return $user;
    }

    public function testCalculateWithEmptyEnd()
    {
        $record = new Timesheet();
        $record->setBegin(new \DateTime());
        $this->assertEquals(0, $record->getRate());

        $sut = new RateCalculator([]);
        $sut->calculate($record);
        $this->assertEquals(0, $record->getRate());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider getDisallowedFactors
     */
    public function testCalculateWithInvalidFactor($factor)
    {
        $today = new \DateTime();
        $day = $today->format('l');
        $rules = [
            'default' => [
                'days' => [$day],
                'factor' => $factor
            ],
        ];
        $seconds = 41837;

        $end = new \DateTime();
        $start = clone $end;
        $start->setTimestamp($end->getTimestamp() - $seconds);

        $record = new Timesheet();
        $record->setUser($this->getTestUser());
        $record->setBegin($start);
        $record->setDuration($seconds);
        $this->assertEquals(0, $record->getRate());

        $record->setEnd($end);

        $sut = new RateCalculator($rules);
        $sut->calculate($record);
    }

    public function getDisallowedFactors()
    {
        return [
            [0],
            [-1],
        ];
    }

    /**
     * @dataProvider getRuleDefinitions
     */
    public function testCalculateWithRules($rules, $expectedFactor)
    {
        $seconds = 41837;

        $end = new \DateTime();
        $start = clone $end;
        $start->setTimestamp($end->getTimestamp() - $seconds);

        $record = new Timesheet();
        $record->setUser($this->getTestUser());
        $record->setBegin($start);
        $record->setDuration($seconds);
        $this->assertEquals(0, $record->getRate());

        $record->setEnd($end);

        $sut = new RateCalculator($rules);
        $sut->calculate($record);

        $this->assertEquals(
            $this->rateForSeconds(self::HOURLY_RATE, $seconds) * $expectedFactor,
            $record->getRate()
        );
    }

    public function getRuleDefinitions()
    {
        $start = new \DateTime();
        $start->setTime(12, 0, 0);
        $day = $start->format('l');

        return [
            [
                [],
                1
            ],
            [
                [
                    'default' => [
                        'days' => [$day],
                        'factor' => 2.0
                    ],
                    'foo' => [
                        'days' => ['bar'],
                        'factor' => 1.5
                    ],
                ],
                2.0
            ],
            [
                [
                    'default' => [
                        'days' => [$day],
                        'factor' => 2.0
                    ],
                    'weekdays' => [
                        'days' => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
                        'factor' => 1.5
                    ],
                ],
                3.5
            ],
        ];
    }

    protected function rateForSeconds($hourlyRate, $seconds)
    {
        return (float) $hourlyRate * ($seconds / 3600);
    }
}
