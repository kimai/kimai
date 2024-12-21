<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

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
use App\Timesheet\RateService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\RateService
 */
class RateServiceTest extends TestCase
{
    protected function getRateRepositoryMock(array $rates = [])
    {
        $mock = $this->getMockBuilder(TimesheetRepository::class)->disableOriginalConstructor()->getMock();
        if (!empty($rates)) {
            $mock->expects($this->any())->method('findMatchingRates')->willReturn($rates);
        }

        return $mock;
    }

    private static function createDateTime(string $datetime = null): \DateTime
    {
        return new \DateTime($datetime ?? 'now', new \DateTimeZone('UTC'));
    }

    public function testCalculateWithTimesheetHourlyRate(): void
    {
        $record = new Timesheet();
        $record->setEnd(self::createDateTime());
        $record->setDuration(1800);
        $record->setHourlyRate(100);
        $record->setActivity(new Activity());
        $record->setUser($this->getTestUser());

        $sut = new RateService([], $this->getRateRepositoryMock());
        $rate = $sut->calculate($record);
        self::assertEquals(50, $rate->getRate());
    }

    public function testCalculateWithTimesheetFixedRate(): void
    {
        $record = new Timesheet();
        $record->setEnd(self::createDateTime());
        $record->setDuration(1800);
        $record->setFixedRate(10);
        // make sure that fixed rate is always applied, even if hourly rate is set
        $record->setHourlyRate(99);
        $record->setActivity(new Activity());
        $record->setUser($this->getTestUser());

        $sut = new RateService([], $this->getRateRepositoryMock());
        $rate = $sut->calculate($record);
        self::assertEquals(10, $rate->getRate());
    }

    public static function getRateTestData()
    {   //             expected, expInt, durat, userH,  userIn, timeH,  timeF,  actH,   actIn,  actF,    proH,   proIn,  proFi,   custH,  custIn, custF
        yield 'a0' => [0.0,     0.0,    0,      0,      0,      null,   null,   null,   null,   false,   null,   null,   false,   null,   null,   false];
        yield 'a2' => [0.0,     0.0,    0,      0,      null,   null,   null,   null,   null,   false,   null,   null,   false,   null,   null,   false];
        yield 'a4' => [0.0,     0.0,    1800,   0,      0,      null,   null,   null,   null,   false,   null,   null,   false,   null,   null,   false];
        yield 'a6' => [0.5,     6.72,   1800,   1,      13.44,  null,   null,   null,   null,   false,   null,   null,   false,   null,   null,   false];
        yield 'a8' => [0.0,     1,      0,      0,      0,      0,      0,      0,      1,      true,    0,      null,   true,    0,      null,   true];
        // rate: 1.5 => timesheet hourly rate , internal: 2.5 => activity hourly rate (30 min)
        yield 'b1' => [1.5,     0.5,    1800,   1,      1,      3,      null,   5,      null,   false,   7,      null,   false,   9,      null,   false];
        yield 'b2' => [2.5,     4.5,    1800,   1,      9,      null,   null,   5,      null,   false,   7,      null,   false,   9,      null,   false];
        yield 'b3' => [3.5,     6.5,    1800,   1,      1,      null,   null,   null,   null,   false,   7,      13,     false,   9,      9,      false];
        yield 'b4' => [4.5,     6.5,    1800,   1,      15,     null,   null,   null,   null,   false,   null,   null,   false,   9,      13,     false];
        // rate: 2.0 => timesheet fixed rate , internal: 3.0 => activity fixed rate
        yield 'b5' => [2.0,     1.0,    1800,   1,      1,      null,   2,      3,      null,   true,    4,      null,   true,    5,      null,   true];
        yield 'b6' => [3.0,     3.0,    1800,   1,      3,      null,   null,   3,      null,   true,    4,      null,   true,    5,      null,   true];
        yield 'b7' => [4.0,     7.0,    1800,   1,      7,      null,   null,   null,   null,   false,   4,      null,   true,    5,      null,   true];
        yield 'b8' => [3.0,     4.7,    1800,   1,      4.7,    null,   null,   3,      null,   true,    null,   null,   false,   5,      null,   true];
        // rate: 2.0 => timesheet fixed rate , internal: 5.0 => customer hourly rate
        yield 'b9' => [2.0,     71.0,   1800,   1,      71,     null,   2,      null,   null,   false,   null,   null,   false,   5,      null,   true];
        // rate: 5.0 => timesheet hourly rate , internal: 7.5 => user internal rate (30 min)
        yield 'c0' => [5.0,     7.5,    1800,   100,    15,     10,     null,   null,   null,   false,   null,   null,   false,   null,   null,   false];
        // internal: 10 because no rule applies and as fallback the users internal rate is used
        yield 'd0' => [10,      100,    1800,   100,    100,    null,   10,     null,   null,   false,   null,   null,   false,   null,   null,   false];
        yield 'e0' => [10,      50,     1800,   100,    100,    null,   null,   20,     null,   false,   null,   null,   false,   null,   null,   false];
        yield 'f0' => [20,      78,     1800,   100,    100,    null,   null,   20,     78,     true,    null,   null,   false,   null,   null,   false];
        yield 'g0' => [15,      11.5,   1800,   100,    100,    null,   null,   null,   null,   false,   30,     23,     false,   null,   null,   false];
        yield 'h0' => [30,      100,    1800,   100,    100,    null,   null,   null,   null,   false,   30,     null,   true,    null,   null,   false];
        yield 'i0' => [20,      13.5,   1800,   100,    100,    null,   null,   null,   null,   false,   null,   null,   false,   40,     27,     false];
        yield 'j0' => [40,      84,     1800,   100,    45,     null,   null,   null,   null,   false,   null,   null,   false,   40,     84,     true];
        // make sure the last fallback for the internal rate is the users hourly rate
        yield 'k0' => [8.82,    6,      1800,   17.64,  12,     null,   null,   null,   null,   false,   null,   null,   false,   null,   null,   true];
        yield 'k1' => [8.82,    8.82,   1800,   17.64,  null,   null,   null,   null,   null,   false,   null,   null,   false,   null,   null,   true];
    }

    /**
     * @dataProvider getRateTestData
     */
    public function testRates(
        $expectedRate,
        $expectedInternalRate,
        $duration,
        $userRate,
        $userInternalRate,
        $timesheetHourly,
        $timesheetFixed,
        $activityRate,
        $activityInternal,
        $activityIsFixed,
        $projectRate,
        $projectInternal,
        $projectIsFixed,
        $customerRate,
        $customerInternal,
        $customerIsFixed
    ) {
        $customer = new Customer('foo');

        $project = new Project();
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet
            ->setEnd(self::createDateTime())
            ->setHourlyRate($timesheetHourly)
            ->setFixedRate($timesheetFixed)
            ->setActivity($activity)
            ->setProject($project)
            ->setDuration($duration)
            ->setUser($this->getTestUser($userRate, $userInternalRate))
        ;

        $rates = [];

        if (null !== $customerRate) {
            $rate = new CustomerRate();
            $rate->setRate($customerRate);
            $rate->setIsFixed($customerIsFixed);
            if (null !== $customerInternal) {
                $rate->setInternalRate($customerInternal);
            }
            $rates[] = $rate;
        }

        if (null !== $projectRate) {
            $rate = new ProjectRate();
            $rate->setRate($projectRate);
            $rate->setIsFixed($projectIsFixed);
            if (null !== $projectInternal) {
                $rate->setInternalRate($projectInternal);
            }
            $rates[] = $rate;
        }

        if (null !== $activityRate) {
            $rate = new ActivityRate();
            $rate->setRate($activityRate);
            $rate->setIsFixed($activityIsFixed);
            if (null !== $activityInternal) {
                $rate->setInternalRate($activityInternal);
            }
            $rates[] = $rate;
        }

        $sut = new RateService([], $this->getRateRepositoryMock($rates));
        $rate = $sut->calculate($timesheet);
        self::assertEquals($expectedRate, $rate->getRate());
        self::assertEquals($expectedInternalRate, $rate->getInternalRate());
    }

    protected function getTestUser($rate = 75, $internalRate = 75)
    {
        $user = new User();

        $pref = new UserPreference(UserPreference::HOURLY_RATE, $rate);
        $prefInt = new UserPreference(UserPreference::INTERNAL_RATE, $internalRate);

        $user->setPreferences([$pref, $prefInt]);

        return $user;
    }

    public function testCalculateWithEmptyEnd(): void
    {
        $record = new Timesheet();
        $record->setBegin(self::createDateTime());
        $record->setDuration(1800);
        $record->setFixedRate(100);
        $record->setHourlyRate(100);
        $record->setActivity(new Activity());

        self::assertEquals(0, $record->getRate());

        $sut = new RateService([], $this->getRateRepositoryMock());
        $rate = $sut->calculate($record);
        self::assertEquals(0, $rate->getRate());
    }

    /**
     * Uses the hourly rate from user_preferences to calculate the rate.
     *
     * @dataProvider getRuleDefinitions
     */
    public function testCalculateWithRulesByUsersHourlyRate($duration, $rules, $expectedRate): void
    {
        $end = self::createDateTime('12:00:00');
        $start = clone $end;
        $start->setTimestamp($end->getTimestamp() - $duration);

        $record = new Timesheet();
        $record->setUser($this->getTestUser());
        $record->setBegin($start);
        $record->setDuration($duration);
        $record->setActivity(new Activity());

        self::assertEquals(0, $record->getRate());

        $record->setEnd($end);

        $sut = new RateService($rules, $this->getRateRepositoryMock());
        $rate = $sut->calculate($record);

        self::assertEquals($expectedRate, $rate->getRate());
    }

    public static function getRuleDefinitions()
    {
        $start = self::createDateTime('12:00:00');
        $day = $start->format('l');

        return [
            [
                31837,
                [],
                663.2708
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
                1326.5417
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
                2321.4479
            ],
        ];
    }
}
