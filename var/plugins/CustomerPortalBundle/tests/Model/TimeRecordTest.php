<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\tests\Model;

use App\Entity\Timesheet;
use App\Entity\User;
use DateTime;
use KimaiPlugin\CustomerPortalBundle\Model\RecordMergeMode;
use KimaiPlugin\CustomerPortalBundle\Model\TimeRecord;
use PHPUnit\Framework\TestCase;

/**
 * @covers \KimaiPlugin\CustomerPortalBundle\Model\TimeRecord
 */
class TimeRecordTest extends TestCase
{
    /**
     * Creates a valid timesheet record from the given parameters.
     * @param DateTime $date date of the timesheet
     * @param User $user user of the timesheet
     * @param float $hourlyRate hour rate
     * @param int $duration duration in seconds
     * @param string $description record description
     * @return Timesheet
     */
    private static function createTimesheet(DateTime $date, User $user, float $hourlyRate, int $duration, ?string $description): Timesheet
    {
        $t = new Timesheet();
        $t->setBegin($date);
        $t->setUser($user);
        $t->setHourlyRate($hourlyRate);
        $t->setRate($hourlyRate * $duration / 60 / 60);
        $t->setDuration($duration);
        $t->setDescription($description);

        return $t;
    }

    public function testInvalidTimesheet(): void
    {
        $this->expectErrorMessage('null given');
        TimeRecord::fromTimesheet(new Timesheet());
    }

    public function testValidEmptyTimesheet(): void
    {
        $begin = new DateTime();
        $user = new User();

        $timeRecord = TimeRecord::fromTimesheet(
            self::createTimesheet($begin, $user, 0, 0, null)
        );

        self::assertNotNull($timeRecord);
        self::assertEquals($begin, $timeRecord->getDate());
        self::assertEquals($user, $timeRecord->getUser());
        self::assertNull($timeRecord->getDescription());
        self::assertEquals(0.0, $timeRecord->getRate());
        self::assertEquals(0, $timeRecord->getDuration());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals([], $timeRecord->getHourlyRates());
    }

    public function testValidFilledTimesheet(): void
    {
        $hours = 2.1;
        $hourlyRate = 123.456;
        $rate = $hours * $hourlyRate;
        $duration = $hours * 60 * 60;
        $description = 'description';

        $timeRecord = TimeRecord::fromTimesheet(
            self::createTimesheet(new DateTime(), new User(), $hourlyRate, $duration, $description)
        );

        self::assertEquals($duration, $timeRecord->getDuration());
        self::assertEquals($rate, $timeRecord->getRate());
        self::assertEquals($description, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($hourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($duration, $timeRecord->getHourlyRates()[0]['duration']);
    }

    public function testMergeModeNull(): void
    {
        $this->expectException(\TypeError::class);

        TimeRecord::fromTimesheet(
            self::createTimesheet(new DateTime(), new User(), 0, 0, null),
            null
        );
    }

    public function testMergeModeNone(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TimeRecord::fromTimesheet(
            self::createTimesheet(new DateTime(), new User(), 0, 0, null),
            RecordMergeMode::MODE_NONE
        );
    }

    public function testMergeModeRandom(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TimeRecord::fromTimesheet(
            self::createTimesheet(new DateTime(), new User(), 0, 0, null),
            uniqid()
        );
    }

    public function testMergeModeDefaultSameRate(): void
    {
        $hourlyRate = 123.456;

        $firstRecordHours = 2.1;
        $firstRecordRate = $firstRecordHours * $hourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordRate = $secondRecordHours * $hourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($firstRecordDescription . PHP_EOL . $secondRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($hourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
    }

    public function testMergeModeUseFirstSameRate(): void
    {
        $hourlyRate = 123.456;

        $firstRecordHours = 2.1;
        $firstRecordRate = $firstRecordHours * $hourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordRate = $secondRecordHours * $hourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_FIRST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($firstRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($hourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
    }

    public function testMergeModeUseLastSameRate(): void
    {
        $hourlyRate = 123.456;

        $firstRecordHours = 2.1;
        $firstRecordRate = $firstRecordHours * $hourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordRate = $secondRecordHours * $hourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($secondRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($hourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
    }

    public function testMergeModeDefaultDifferentRate(): void
    {
        $firstRecordHours = 2.1;
        $firstRecordHourlyRate = 123.456;
        $firstRecordRate = $firstRecordHours * $firstRecordHourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordHourlyRate = 234.567;
        $secondRecordRate = $secondRecordHours * $secondRecordHourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $firstRecordHourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $secondRecordHourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($firstRecordDescription . PHP_EOL . $secondRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(true, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($firstRecordHourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
        self::assertEquals($secondRecordHourlyRate, $timeRecord->getHourlyRates()[1]['hourlyRate']);
        self::assertEquals($secondRecordDuration, $timeRecord->getHourlyRates()[1]['duration']);
    }

    public function testMergeModeUseFirstDifferentRate(): void
    {
        $firstRecordHours = 2.1;
        $firstRecordHourlyRate = 123.456;
        $firstRecordRate = $firstRecordHours * $firstRecordHourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordHourlyRate = 234.567;
        $secondRecordRate = $secondRecordHours * $secondRecordHourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $firstRecordHourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $secondRecordHourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_FIRST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($firstRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(true, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($firstRecordHourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
        self::assertEquals($secondRecordHourlyRate, $timeRecord->getHourlyRates()[1]['hourlyRate']);
        self::assertEquals($secondRecordDuration, $timeRecord->getHourlyRates()[1]['duration']);
    }

    public function testMergeModeUseLastDifferentRate(): void
    {
        $firstRecordHours = 2.1;
        $firstRecordHourlyRate = 123.456;
        $firstRecordRate = $firstRecordHours * $firstRecordHourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordHourlyRate = 234.567;
        $secondRecordRate = $secondRecordHours * $secondRecordHourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $firstRecordHourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $secondRecordHourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($secondRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(true, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($firstRecordHourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
        self::assertEquals($secondRecordHourlyRate, $timeRecord->getHourlyRates()[1]['hourlyRate']);
        self::assertEquals($secondRecordDuration, $timeRecord->getHourlyRates()[1]['duration']);
    }
}
