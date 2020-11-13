<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Timesheet\UserDateTimeFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Timesheet\DateTimeFactory
 * @covers \App\Timesheet\UserDateTimeFactory
 */
class UserDateTimeFactoryTest extends TestCase
{
    public const TEST_TIMEZONE = 'Africa/Asmara';

    protected function createUserDateTimeFactory(?string $timezone = null): UserDateTimeFactory
    {
        return (new UserDateTimeFactoryFactory($this))->create($timezone);
    }

    public function testGetTimezone()
    {
        $sut = $this->createUserDateTimeFactory(self::TEST_TIMEZONE);
        $this->assertEquals(self::TEST_TIMEZONE, $sut->getTimezone()->getName());
    }

    public function testGetTimezoneWithFallbackTimezone()
    {
        $sut = $this->createUserDateTimeFactory();
        $this->assertEquals(date_default_timezone_get(), $sut->getTimezone()->getName());
    }
}
