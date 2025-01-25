<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Timesheet\TrackingMode\PunchInOutMode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @covers \App\Timesheet\TrackingMode\PunchInOutMode
 */
class PunchInOutModeTest extends TestCase
{
    private function createSut(bool $allowApiTimes = false): PunchInOutMode
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturn($allowApiTimes);

        return new PunchInOutMode($auth);
    }

    public function testDefaultValues(): void
    {
        $sut = $this->createSut();

        self::assertFalse($sut->canEditBegin());
        self::assertFalse($sut->canEditEnd());
        self::assertFalse($sut->canEditDuration());
        self::assertFalse($sut->canUpdateTimesWithAPI());
        self::assertTrue($sut->canSeeBeginAndEndTimes());
        self::assertEquals('punch', $sut->getId());
    }

    public function testValuesForAdmin(): void
    {
        $sut = $this->createSut(true);

        self::assertFalse($sut->canEditBegin());
        self::assertFalse($sut->canEditEnd());
        self::assertFalse($sut->canEditDuration());
        self::assertTrue($sut->canUpdateTimesWithAPI());
        self::assertTrue($sut->canSeeBeginAndEndTimes());
        self::assertEquals('punch', $sut->getId());
    }

    public function testCreate(): void
    {
        $startingTime = new \DateTime('22:54');
        $timesheet = new Timesheet();
        $timesheet->setBegin($startingTime);
        $request = new Request();

        $sut = $this->createSut();
        $sut->create($timesheet, $request);
        self::assertEquals($timesheet->getBegin(), $startingTime);
    }

    public function testCreateWithoutBegin(): void
    {
        $timesheet = (new Timesheet())->setUser(new User());
        $request = new Request();

        $sut = $this->createSut();
        $sut->create($timesheet, $request);
        self::assertInstanceOf(\DateTime::class, $timesheet->getBegin());
    }
}
