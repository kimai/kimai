<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Timesheet\DateTimeFactory;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\DateTimeFactory
 */
class DateTimeFactoryTest extends TestCase
{
    public const TEST_TIMEZONE = 'Europe/London';

    protected static function createDateTimeFactory(?string $timezone = null, bool $sunday = false): DateTimeFactory
    {
        if (null === $timezone) {
            return new DateTimeFactory(null, $sunday);
        }

        return new DateTimeFactory(new DateTimeZone($timezone), $sunday);
    }

    public function testGetTimezone(): void
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        self::assertEquals(self::TEST_TIMEZONE, $sut->getTimezone()->getName());
    }

    public function testGetTimezoneWithFallbackTimezone(): void
    {
        $sut = $this->createDateTimeFactory();
        self::assertEquals(date_default_timezone_get(), $sut->getTimezone()->getName());
    }

    public function testGetStartOfMonth(): void
    {
        $expected = new DateTime('now', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->getStartOfMonth();
        self::assertEquals(0, $dateTime->format('H'));
        self::assertEquals(0, $dateTime->format('i'));
        self::assertEquals(0, $dateTime->format('s'));
        self::assertEquals(1, $dateTime->format('d'));
        self::assertEquals($expected->format('m'), $dateTime->format('m'));
        self::assertEquals($expected->format('Y'), $dateTime->format('Y'));
        self::assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testGetEndOfMonth(): void
    {
        $expected = new DateTime('last day of this month', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->getEndOfMonth();
        self::assertEquals(23, $dateTime->format('H'));
        self::assertEquals(59, $dateTime->format('i'));
        self::assertEquals(59, $dateTime->format('s'));
        self::assertEquals($expected->format('d'), $dateTime->format('d'));
        self::assertEquals($expected->format('m'), $dateTime->format('m'));
        self::assertEquals($expected->format('Y'), $dateTime->format('Y'));
        self::assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public static function getStartOfWeekData()
    {
        yield [self::createDateTimeFactory(self::TEST_TIMEZONE), 'Monday', 23, 1];
        yield [self::createDateTimeFactory(self::TEST_TIMEZONE, false), 'Monday', 23, 1];
        yield [self::createDateTimeFactory(self::TEST_TIMEZONE, true), 'Sunday', 22, 7];
    }

    /**
     * @dataProvider getStartOfWeekData
     */
    public function testGetStartOfWeek(DateTimeFactory $sut, string $dayName, int $dayNum, int $day): void
    {
        $expected = new DateTime('2018-07-26 16:47:31', new DateTimeZone(self::TEST_TIMEZONE));

        $dateTime = $sut->getStartOfWeek($expected);

        self::assertEquals(0, $dateTime->format('H'));
        self::assertEquals(0, $dateTime->format('i'));
        self::assertEquals(0, $dateTime->format('s'));
        self::assertEquals($dayNum, $dateTime->format('d'));
        self::assertEquals($day, $dateTime->format('N'));
        self::assertEquals($dayName, $dateTime->format('l'));
        self::assertEquals($expected->format('m'), $dateTime->format('m'));
        self::assertEquals($expected->format('Y'), $dateTime->format('Y'));
        self::assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());

        $dateTime = $sut->getStartOfWeek();

        self::assertEquals(0, $dateTime->format('H'));
        self::assertEquals(0, $dateTime->format('i'));
        self::assertEquals(0, $dateTime->format('s'));
        self::assertEquals($day, $dateTime->format('N'));
        self::assertEquals($dayName, $dateTime->format('l'));
        // month and year can be different when the week started at the end of the month
        self::assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public static function getEndOfWeekData()
    {
        yield [self::createDateTimeFactory(self::TEST_TIMEZONE), 'Sunday', 29, 7];
        yield [self::createDateTimeFactory(self::TEST_TIMEZONE, false), 'Sunday', 29, 7];
        yield [self::createDateTimeFactory(self::TEST_TIMEZONE, true), 'Saturday', 28, 6];
    }

    /**
     * @dataProvider getEndOfWeekData
     */
    public function testGetEndOfWeek(DateTimeFactory $sut, string $dayName, int $dayNum, int $day): void
    {
        $expected = new DateTime('2018-07-26 16:47:31', new DateTimeZone(self::TEST_TIMEZONE));

        $dateTime = $sut->getEndOfWeek($expected);

        self::assertEquals(23, $dateTime->format('H'));
        self::assertEquals(59, $dateTime->format('i'));
        self::assertEquals(59, $dateTime->format('s'));
        self::assertEquals($dayNum, $dateTime->format('d'));
        self::assertEquals($day, $dateTime->format('N'));
        self::assertEquals($dayName, $dateTime->format('l'));
        self::assertEquals('07', $dateTime->format('m'));
        self::assertEquals('2018', $dateTime->format('Y'));
        self::assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());

        $dateTime = $sut->getEndOfWeek();

        self::assertEquals(23, $dateTime->format('H'));
        self::assertEquals(59, $dateTime->format('i'));
        self::assertEquals(59, $dateTime->format('s'));
        self::assertEquals($day, $dateTime->format('N'));
        self::assertEquals($dayName, $dateTime->format('l'));
        // month and year can be different when the week started at the end of the month
        self::assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testCreateDateTime(): void
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->createDateTime('2015-07-24 13:45:21');
        self::assertEquals(13, $dateTime->format('H'));
        self::assertEquals(45, $dateTime->format('i'));
        self::assertEquals(21, $dateTime->format('s'));
        self::assertEquals('24', $dateTime->format('d'));
        self::assertEquals('07', $dateTime->format('m'));
        self::assertEquals('2015', $dateTime->format('Y'));
        self::assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testCreateDateTimeWithDefaultValue(): void
    {
        $expected = new DateTime('now', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->createDateTime();
        $difference = $expected->getTimestamp() - $dateTime->getTimestamp();
        // poor test, but there shouldn't be more than 2 seconds between the creation of two DateTime objects
        self::assertTrue(2 >= $difference);
    }

    public function testCreateStartOfFinancialYearWithoutConfig(): void
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->createStartOfFinancialYear();
        $expected = $sut->createDateTime('01 january this year 00:00:00');
        self::assertInstanceOf(DateTime::class, $dateTime);
        self::assertEquals($expected, $dateTime);
    }

    public function testCreateStartOfFinancialYearWithConfig(): void
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);

        $future = $sut->createDateTime('+10 days');
        $past = $sut->createDateTime('-10 days');

        $financial = $sut->createStartOfFinancialYear($future->format('Y-m-d'));

        $future->modify('-1 year');
        $future->setTime(0, 0, 0);

        self::assertEquals($future, $financial);

        $financial = $sut->createStartOfFinancialYear($past->format('Y-m-d'));

        $past->setTime(0, 0, 0);
        self::assertEquals($past, $financial);
    }

    public function testCreateEndOfFinancialYearWithConfig(): void
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);

        $now = $sut->createDateTime();
        $expected = $sut->createDateTime();
        $expected = $expected->setDate((int) $expected->format('Y'), 7, 22);
        $expected = $expected->setTime(23, 59, 59);

        if ($now > $expected) {
            $expected->modify('+1 year');
        }

        $financial = $sut->createStartOfFinancialYear('2018-07-23 00:00:00');
        $end = $sut->createEndOfFinancialYear($financial);

        self::assertEquals($expected, $end);
    }

    public function testCreateStartOfYear(): void
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);

        $now = $sut->createDateTime();
        $year = $sut->createStartOfYear();
        self::assertEquals($now->format('Y'), $year->format('Y'));
        self::assertEquals('01', $year->format('m'));
        self::assertEquals('01', $year->format('d'));
        self::assertEquals('00:00:00', $year->format('H:i:s'));
        $now->setTime(0, 0, 0);
        self::assertEquals($now->format('H:i:s'), $year->format('H:i:s'));

        $begin = $sut->createDateTime('2017-12-31 23:59:59');
        $year = $sut->createStartOfYear($begin);
        self::assertEquals('2017', $year->format('Y'));
        self::assertEquals('01', $year->format('m'));
        self::assertEquals('01', $year->format('d'));
        self::assertEquals('00:00:00', $year->format('H:i:s'));
    }

    public function testCreateEndOfYear(): void
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);

        $now = $sut->createDateTime();
        $year = $sut->createEndOfYear();
        self::assertEquals($now->format('Y'), $year->format('Y'));
        self::assertEquals('12', $year->format('m'));
        self::assertEquals('31', $year->format('d'));
        self::assertEquals('23:59:59', $year->format('H:i:s'));
        $now->setTime(23, 59, 59);
        self::assertEquals($now->format('H:i:s'), $year->format('H:i:s'));

        $begin = $sut->createDateTime('2017-12-31 23:59:59');
        $year = $sut->createEndOfYear($begin);
        self::assertEquals('2017', $year->format('Y'));
        self::assertEquals('12', $year->format('m'));
        self::assertEquals('31', $year->format('d'));
        self::assertEquals('23:59:59', $year->format('H:i:s'));
    }
}
