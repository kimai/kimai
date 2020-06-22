<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Timesheet\TrackingMode\PunchInOutMode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Timesheet\TrackingMode\PunchInOutMode
 */
class PunchInOutModeTest extends TestCase
{
    public function testDefaultValues()
    {
        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $sut = new PunchInOutMode($dateTime);

        self::assertFalse($sut->canEditBegin());
        self::assertFalse($sut->canEditEnd());
        self::assertFalse($sut->canEditDuration());
        self::assertFalse($sut->canUpdateTimesWithAPI());
        self::assertTrue($sut->canSeeBeginAndEndTimes());
        self::assertEquals('punch', $sut->getId());
    }

    public function testCreate()
    {
        $startingTime = new \DateTime('22:54');
        $timesheet = new Timesheet();
        $timesheet->setBegin($startingTime);
        $request = new Request();

        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $sut = new PunchInOutMode($dateTime);
        $sut->create($timesheet, $request);
        self::assertEquals($timesheet->getBegin(), $startingTime);
    }

    public function testCreateWithoutBegin()
    {
        $timesheet = new Timesheet();
        $request = new Request();

        $dateTime = (new UserDateTimeFactoryFactory($this))->create();
        $sut = new PunchInOutMode($dateTime);
        $sut->create($timesheet, $request);
        self::assertInstanceOf(\DateTime::class, $timesheet->getBegin());
    }
}
