<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\TrackingMode;

use App\Entity\Timesheet;
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
        $sut = new PunchInOutMode();

        self::assertFalse($sut->canEditBegin());
        self::assertFalse($sut->canEditEnd());
        self::assertFalse($sut->canEditDuration());
        self::assertFalse($sut->canUpdateTimesWithAPI());
        self::assertEquals('punch', $sut->getId());
    }

    public function testCreate()
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime('22:54'));
        $request = new Request();
        $timesheetNew = clone $timesheet;

        $sut = new PunchInOutMode();
        $sut->create($timesheet, $request);
        self::assertEquals($timesheet, $timesheetNew);
    }
}
