<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\User;
use App\Export\InitialTimeRangeFactory;
use App\Form\Type\ExportTimeRangeType;
use App\Tests\Mocks\Security\CurrentUserFactory;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\InitialTimeRangeFactory
 */
class InitialTimeRangeFactoryTest extends TestCase
{
    public const TEST_TIMEZONE = 'Europe/London';

    protected function createUser(?string $timezone = null, ?string $initialTimeRange = null): User
    {
        $user = (new CurrentUserFactory($this))->create(new User(), $timezone)->getUser();
        if ($initialTimeRange) {
            $user->setPreferenceValue('export.initial_time_range', $initialTimeRange);
        }

        return $user;
    }

    public function testGetRange()
    {
        $sut = new InitialTimeRangeFactory();
        $user = $this->createUser(self::TEST_TIMEZONE);

        $range = $sut->getRange($user);
        // Test result array
        $this->assertCount(2, $range);

        // Test start
        $this->assertArrayHasKey(0, $range);
        $start = $range[0];
        $this->assertInstanceOf(DateTime::class, $start);

        // Test end
        $this->assertArrayHasKey(1, $range);
        $end = $range[1];
        $this->assertInstanceOf(DateTime::class, $end);

        // Test if end > start
        $this->assertGreaterThan($start, $end);

        // Test if start equals result of getStart()
        $this->assertEquals($start, $sut->getStart($user));

        // Test if end equals result of getEnd()
        $this->assertEquals($end, $sut->getEnd($user));
    }

    public function testGetStartMonth()
    {
        $expected = new DateTime('now', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = new InitialTimeRangeFactory();
        $user = $this->createUser(self::TEST_TIMEZONE, ExportTimeRangeType::TIME_RANGE_CURRENT_MONTH);

        $dateTime = $sut->getStart($user);
        $this->assertEquals(0, $dateTime->format('H'));
        $this->assertEquals(0, $dateTime->format('i'));
        $this->assertEquals(0, $dateTime->format('s'));
        $this->assertEquals(1, $dateTime->format('d'));
        $this->assertEquals($expected->format('m'), $dateTime->format('m'));
        $this->assertEquals($expected->format('Y'), $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testGetStartYear()
    {
        $expected = new DateTime('now', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = new InitialTimeRangeFactory();
        $user = $this->createUser(self::TEST_TIMEZONE, ExportTimeRangeType::TIME_RANGE_CURRENT_YEAR);

        $dateTime = $sut->getStart($user);
        $this->assertEquals(0, $dateTime->format('H'));
        $this->assertEquals(0, $dateTime->format('i'));
        $this->assertEquals(0, $dateTime->format('s'));
        $this->assertEquals(1, $dateTime->format('d'));
        $this->assertEquals(1, $dateTime->format('m'));
        $this->assertEquals($expected->format('Y'), $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testGetStartDecade()
    {
        $expected = new DateTime('now', new DateTimeZone(self::TEST_TIMEZONE));
        $expectedDecade = (int) (floor((int) $expected->format('Y') / 10) * 10);

        $sut = new InitialTimeRangeFactory();
        $user = $this->createUser(self::TEST_TIMEZONE, ExportTimeRangeType::TIME_RANGE_CURRENT_DECADE);

        $dateTime = $sut->getStart($user);
        $this->assertEquals(0, $dateTime->format('H'));
        $this->assertEquals(0, $dateTime->format('i'));
        $this->assertEquals(0, $dateTime->format('s'));
        $this->assertEquals(1, $dateTime->format('d'));
        $this->assertEquals(1, $dateTime->format('m'));
        $this->assertEquals($expectedDecade, $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testGetEndMonth()
    {
        $expected = new DateTime('last day of this month', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = new InitialTimeRangeFactory();
        $user = $this->createUser(self::TEST_TIMEZONE, ExportTimeRangeType::TIME_RANGE_CURRENT_MONTH);

        $dateTime = $sut->getEnd($user);
        $this->assertEquals(23, $dateTime->format('H'));
        $this->assertEquals(59, $dateTime->format('i'));
        $this->assertEquals(59, $dateTime->format('s'));
        $this->assertEquals($expected->format('d'), $dateTime->format('d'));
        $this->assertEquals($expected->format('m'), $dateTime->format('m'));
        $this->assertEquals($expected->format('Y'), $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testGetEndYear()
    {
        $expected = new DateTime('last day of december this year', new DateTimeZone(self::TEST_TIMEZONE));

        $sut = new InitialTimeRangeFactory();
        $user = $this->createUser(self::TEST_TIMEZONE, ExportTimeRangeType::TIME_RANGE_CURRENT_YEAR);

        $dateTime = $sut->getEnd($user);
        $this->assertEquals(23, $dateTime->format('H'));
        $this->assertEquals(59, $dateTime->format('i'));
        $this->assertEquals(59, $dateTime->format('s'));
        $this->assertEquals($expected->format('d'), $dateTime->format('d'));
        $this->assertEquals($expected->format('m'), $dateTime->format('m'));
        $this->assertEquals($expected->format('Y'), $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }

    public function testGetEndDecade()
    {
        $expected = new DateTime('last day of december this year', new DateTimeZone(self::TEST_TIMEZONE));
        $expectedDecade = (int) ((ceil((int) $expected->format('Y') / 10) * 10) - 1);

        $sut = new InitialTimeRangeFactory();
        $user = $this->createUser(self::TEST_TIMEZONE, ExportTimeRangeType::TIME_RANGE_CURRENT_DECADE);

        $dateTime = $sut->getEnd($user);
        $this->assertEquals(23, $dateTime->format('H'));
        $this->assertEquals(59, $dateTime->format('i'));
        $this->assertEquals(59, $dateTime->format('s'));
        $this->assertEquals($expected->format('d'), $dateTime->format('d'));
        $this->assertEquals($expected->format('m'), $dateTime->format('m'));
        $this->assertEquals($expectedDecade, $dateTime->format('Y'));
        $this->assertEquals(self::TEST_TIMEZONE, $dateTime->getTimezone()->getName());
    }
}
