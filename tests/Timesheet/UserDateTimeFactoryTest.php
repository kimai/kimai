<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\UserRepository;
use App\Security\CurrentUser;
use App\Timesheet\UserDateTimeFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @covers \App\Timesheet\UserDateTimeFactory
 */
class UserDateTimeFactoryTest extends TestCase
{
    public const TEST_TIMEZONE = 'Europe/London';

    protected function createDateTimeFactory(string $timezone)
    {
        return new UserDateTimeFactory($this->getCurrentUserMock($timezone));
    }

    protected function getCurrentUserMock($timezone = null)
    {
        $user = new User();
        if (null !== $timezone) {
            $pref = new UserPreference();
            $pref->setName('timezone');
            $pref->setValue($timezone);
            $user->addPreference($pref);
        }
        $repository = $this->getMockBuilder(UserRepository::class)->setMethods(['getById'])->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('getById')->willReturn($user);
        $token = $this->getMockBuilder(UsernamePasswordToken::class)->setMethods(['getUser'])->disableOriginalConstructor()->getMock();
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        return new CurrentUser($tokenStorage, $repository);
    }

    public function testGetTimezone()
    {
        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $this->assertEquals(self::TEST_TIMEZONE, $sut->getTimezone()->getName());
    }

    public function testGetTimezoneWithFallbackTimezone()
    {
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(UsernamePasswordToken::class)->setMethods(['getUser'])->disableOriginalConstructor()->getMock();
        $token->expects($this->once())->method('getUser')->willReturn('anonymous');
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $current = new CurrentUser($tokenStorage, $repository);
        $sut = new UserDateTimeFactory($current);
        $this->assertEquals(date_default_timezone_get(), $sut->getTimezone()->getName());
    }

    public function testGetStartOfMonth()
    {
        $expected = new \DateTime();

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
        $expected = new \DateTime('last day of this month');

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
        $expected = new \DateTime('now', new \DateTimeZone(self::TEST_TIMEZONE));

        $sut = $this->createDateTimeFactory(self::TEST_TIMEZONE);
        $dateTime = $sut->createDateTime();
        $difference = $expected->getTimestamp() - $dateTime->getTimestamp();
        // poor test, but there shouldn't be more than 2 seconds between the creation of two DateTime objects
        $this->assertTrue(2 >= $difference);
    }
}
