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

    protected function createDateTimeFactory(?string $timezone = null, bool $sunday = false): DateTimeFactory
    {
        if (null === $timezone) {
            return new DateTimeFactory(null, $sunday);
        }

        return new DateTimeFactory(new DateTimeZone($timezone), $sunday);
    }

    public function testGetTimezone()
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $this->assertEquals(self::TEST_TIMEZONE, $sut->getTimezone()->getName());
    }

    public function testGetTimezoneWithFallbackTimezone()
    {
        $sut = $this->createDateTimeFactory();
        $this->assertEquals(date_default_timezone_get(), $sut->getTimezone()->getName());
    }

    public function testGetStartOfMonth()
    {
        $expected = new DateTime('now', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->getStartOfMonth();
        $this->assertEquals(0, $dateTime->format('H'));
        $this->assertEquals(0, $dateTime->format('i'));
        $this->assertEquals(0, $dateTime->format('s'));
        $this->assertEquals(1, $dateTime->format('d'));
        $this->assertEquals($expected->format('m'), $dateTime->format('m'));
        $this->assertEquals($expected->format('Y'), $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testGetEndOfMonth()
    {
        $expected = new DateTime('last day of this month', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->getEndOfMonth();
        $this->assertEquals(23, $dateTime->format('H'));
        $this->assertEquals(59, $dateTime->format('i'));
        $this->assertEquals(59, $dateTime->format('s'));
        $this->assertEquals($expected->format('d'), $dateTime->format('d'));
        $this->assertEquals($expected->format('m'), $dateTime->format('m'));
        $this->assertEquals($expected->format('Y'), $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function getStartOfWeekData()
    {
        yield [$this->createDateTimeFactory(self::TEST_TIMEZONE), 'Monday', 23, 1];
        yield [$this->createDateTimeFactory(self::TEST_TIMEZONE, false), 'Monday', 23, 1];
        yield [$this->createDateTimeFactory(self::TEST_TIMEZONE, true), 'Sunday', 22, 7];
    }

    /**
     * @dataProvider getStartOfWeekData
     */
    public function testGetStartOfWeek(DateTimeFactory $sut, string $dayName, int $dayNum, int $day)
    {
        $expected = new DateTime('2018-07-26 16:47:31', new DateTimeZone(self::TEST_TIMEZONE));

        $dateTime = $sut->getStartOfWeek($expected);

        $this->assertEquals(0, $dateTime->format('H'));
        $this->assertEquals(0, $dateTime->format('i'));
        $this->assertEquals(0, $dateTime->format('s'));
        $this->assertEquals($dayNum, $dateTime->format('d'));
        $this->assertEquals($day, $dateTime->format('N'));
        $this->assertEquals($dayName, $dateTime->format('l'));
        $this->assertEquals($expected->format('m'), $dateTime->format('m'));
        $this->assertEquals($expected->format('Y'), $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());

        $dateTime = $sut->getStartOfWeek();

        $this->assertEquals(0, $dateTime->format('H'));
        $this->assertEquals(0, $dateTime->format('i'));
        $this->assertEquals(0, $dateTime->format('s'));
        $this->assertEquals($day, $dateTime->format('N'));
        $this->assertEquals($dayName, $dateTime->format('l'));
        // month and year can be different when the week started at the end of the month
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function getEndOfWeekData()
    {
        yield [$this->createDateTimeFactory(self::TEST_TIMEZONE), 'Sunday', 29, 7];
        yield [$this->createDateTimeFactory(self::TEST_TIMEZONE, false), 'Sunday', 29, 7];
        yield [$this->createDateTimeFactory(self::TEST_TIMEZONE, true), 'Saturday', 28, 6];
    }

    /**
     * @dataProvider getEndOfWeekData
     */
    public function testGetEndOfWeek(DateTimeFactory $sut, string $dayName, int $dayNum, int $day)
    {
        $expected = new DateTime('2018-07-26 16:47:31', new DateTimeZone(self::TEST_TIMEZONE));

        $dateTime = $sut->getEndOfWeek($expected);

        $this->assertEquals(23, $dateTime->format('H'));
        $this->assertEquals(59, $dateTime->format('i'));
        $this->assertEquals(59, $dateTime->format('s'));
        $this->assertEquals($dayNum, $dateTime->format('d'));
        $this->assertEquals($day, $dateTime->format('N'));
        $this->assertEquals($dayName, $dateTime->format('l'));
        $this->assertEquals('07', $dateTime->format('m'));
        $this->assertEquals('2018', $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());

        $dateTime = $sut->getEndOfWeek();

        $this->assertEquals(23, $dateTime->format('H'));
        $this->assertEquals(59, $dateTime->format('i'));
        $this->assertEquals(59, $dateTime->format('s'));
        $this->assertEquals($day, $dateTime->format('N'));
        $this->assertEquals($dayName, $dateTime->format('l'));
        // month and year can be different when the week started at the end of the month
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testCreateDateTime()
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->createDateTime('2015-07-24 13:45:21');
        $this->assertEquals(13, $dateTime->format('H'));
        $this->assertEquals(45, $dateTime->format('i'));
        $this->assertEquals(21, $dateTime->format('s'));
        $this->assertEquals('24', $dateTime->format('d'));
        $this->assertEquals('07', $dateTime->format('m'));
        $this->assertEquals('2015', $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testCreateDateTimeWithDefaultValue()
    {
        $expected = new DateTime('now', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->createDateTime();
        $difference = $expected->getTimestamp() - $dateTime->getTimestamp();
        // poor test, but there shouldn't be more than 2 seconds between the creation of two DateTime objects
        $this->assertTrue(2 >= $difference);
    }
}
