<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\Calculator;

use App\Entity\Activity;
use App\Entity\ActivityRate;
use App\Entity\Customer;
use App\Entity\CustomerRate;
use App\Entity\Project;
use App\Entity\ProjectRate;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\TimesheetRepository;
use App\Timesheet\Calculator\RateCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\Calculator\RateCalculator
 */
class RateCalculatorTest extends TestCase
{
    protected function getRateRepositoryMock(array $rates = [])
    {
        $mock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        if (!empty($rates)) {
            $mock->expects($this->any())->method('findMatchingRates')->willReturn($rates);
        }

        return $mock;
    }

    public function testCalculateWithTimesheetHourlyRate()
    {
        $record = new Timesheet();
        $record->setEnd(new \DateTime());
        $record->setDuration(1800);
        $record->setHourlyRate(100);
        $record->setActivity(new Activity());

        $sut = new RateCalculator([], $this->getRateRepositoryMock());
        $sut->calculate($record);
        $this->assertEquals(50, $record->getRate());
    }

    public function testCalculateWithTimesheetFixedRate()
    {
        $record = new Timesheet();
        $record->setEnd(new \DateTime());
        $record->setDuration(1800);
        $record->setFixedRate(10);
        // make sure that fixed rate is always applied, even if hourly rate is set
        $record->setHourlyRate(99);
        $record->setActivity(new Activity());

        $sut = new RateCalculator([], $this->getRateRepositoryMock());
        $sut->calculate($record);
        $this->assertEquals(10, $record->getRate());
    }

    public function getRateTestData()
    {
        yield 'a0' => [0.0, 0,      0,      null,   null,   null,   null,   null,   null,   null,   null];
        yield 'a1' => [0.0, 1800,   0,      null,   null,   null,   null,   null,   null,   null,   null];
        yield 'a2' => [0.5, 1800,   1,      null,   null,   null,   null,   null,   null,   null,   null];
        yield 'a3' => [0.0, 0,      0,      0,      0,      0,      0,      0,      0,      0,      0];
        yield 'b1' => [1.5, 1800,   1,      3,      null,   5,      null,   7,      null,   9,      null];
        yield 'b2' => [2.5, 1800,   1,      null,   null,   5,      null,   7,      null,   9,      null];
        yield 'b3' => [3.5, 1800,   1,      null,   null,   null,   null,   7,      null,   9,      null];
        yield 'b4' => [4.5, 1800,   1,      null,   null,   null,   null,   null,   null,   9,      null];
        yield 'b5' => [2.0, 1800,   1,      null,   2,      null,   3,      null,   4,      null,   5];
        yield 'b6' => [3.0, 1800,   1,      null,   null,   null,   3,      null,   4,      null,   5];
        yield 'b7' => [4.0, 1800,   1,      null,   null,   null,   null,   null,   4,      null,   5];
        yield 'b8' => [3.0, 1800,   1,      null,   null,   null,   3,      null,   null,   null,   5];
        yield 'b9' => [2.0, 1800,   1,      null,   2,      null,   null,   null,   null,   null,   5];
        yield 'c0' => [5.0, 1800,   100,    10,     null,   null,   null,   null,   null,   null,   null];
        yield 'd0' => [10,  1800,   100,    null,   10,     null,   null,   null,   null,   null,   null];
        yield 'e0' => [10,  1800,   100,    null,   null,   20,     null,   null,   null,   null,   null];
        yield 'f0' => [20,  1800,   100,    null,   null,   null,   20,     null,   null,   null,   null];
        yield 'g0' => [15,  1800,   100,    null,   null,   null,   null,   30,     null,   null,   null];
        yield 'h0' => [30,  1800,   100,    null,   null,   null,   null,   null,   30,     null,   null];
        yield 'i0' => [20,  1800,   100,    null,   null,   null,   null,   null,   null,   40,     null];
        yield 'j0' => [40,  1800,   100,    null,   null,   null,   null,   null,   null,   null,   40];
    }

    /**
     * @dataProvider getRateTestData
     */
    public function testRates(
        $exptectedRate,
        $duration,
        $userRate,
        $timesheetHourly,
        $timesheetFixed,
        $activityHourly,
        $activityFixed,
        $projectHourly,
        $projectFixed,
        $customerHourly,
        $customerFixed
    ) {
        $customer = new Customer();

        $project = new Project();
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet
            ->setEnd(new \DateTime())
            ->setHourlyRate($timesheetHourly)
            ->setFixedRate($timesheetFixed)
            ->setActivity($activity)
            ->setProject($project)
            ->setDuration($duration)
            ->setUser($this->getTestUser($userRate))
        ;

        $rates = [];

        if (null !== $customerFixed) {
            $rates[] = (new CustomerRate())->setRate($customerFixed)->setIsFixed(true);
        } elseif (null !== $customerHourly) {
            $rates[] = (new CustomerRate())->setRate($customerHourly);
        }

        if (null !== $projectFixed) {
            $rates[] = (new ProjectRate())->setRate($projectFixed)->setIsFixed(true);
        } elseif (null !== $projectHourly) {
            $rates[] = (new ProjectRate())->setRate($projectHourly);
        }

        if (null !== $activityFixed) {
            $rates[] = (new ActivityRate())->setRate($activityFixed)->setIsFixed(true);
        } elseif (null !== $activityHourly) {
            $rates[] = (new ActivityRate())->setRate($activityHourly);
        }

        $sut = new RateCalculator([], $this->getRateRepositoryMock($rates));
        $sut->calculate($timesheet);
        $this->assertEquals($exptectedRate, $timesheet->getRate());
    }

    protected function getTestUser($rate = 75)
    {
        $pref = new UserPreference();
        $pref->setName(UserPreference::HOURLY_RATE);
        $pref->setValue($rate);

        $user = new User();
        $user->setPreferences([$pref]);

        return $user;
    }

    public function testCalculateWithEmptyEnd()
    {
        $record = new Timesheet();
        $record->setBegin(new \DateTime());
        $record->setDuration(1800);
        $record->setFixedRate(100);
        $record->setHourlyRate(100);
        $record->setActivity(new Activity());

        $this->assertEquals(0, $record->getRate());

        $sut = new RateCalculator([], $this->getRateRepositoryMock());
        $sut->calculate($record);
        $this->assertEquals(0, $record->getRate());
    }

    /**
     * Uses the hourly rate from user_preferences to calculate the rate.
     *
     * @dataProvider getRuleDefinitions
     */
    public function testCalculateWithRulesByUsersHourlyRate($duration, $rules, $expectedRate)
    {
        $end = new \DateTime('12:00:00', new \DateTimeZone('UTC'));
        $start = clone $end;
        $start->setTimestamp($end->getTimestamp() - $duration);

        $record = new Timesheet();
        $record->setUser($this->getTestUser());
        $record->setBegin($start);
        $record->setDuration($duration);
        $record->setActivity(new Activity());

        $this->assertEquals(0, $record->getRate());

        $record->setEnd($end);

        $sut = new RateCalculator($rules, $this->getRateRepositoryMock());
        $sut->calculate($record);

        $this->assertEquals($expectedRate, $record->getRate());
    }

    public function getRuleDefinitions()
    {
        $start = new \DateTime('12:00:00', new \DateTimeZone('UTC'));
        $day = $start->format('l');

        return [
            [
                31837,
                [],
                663.27
            ],
            [
                31837,
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
                1326.54
            ],
            [
                31837,
                [
                    'default' => [
                        'days' => [$day],
                        'factor' => 2.0
                    ],
                    'foo' => [
                        'days' => ['MonDay', 'tUEsdAy', 'WEdnesday', 'THursday', 'friDay', 'SATURday', 'sunDAY'],
                        'factor' => 1.5
                    ],
                ],
                2321.45
            ],
        ];
    }
}
