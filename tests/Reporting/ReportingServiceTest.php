<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Entity\User;
use App\Event\ReportingEvent;
use App\Reporting\ReportingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @covers \App\Reporting\ReportingService
 */
class ReportingServiceTest extends TestCase
{
    protected function getSut(bool $isGranted = false): ReportingService
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly($isGranted ? 1 : 0))->method('dispatch')->willReturnCallback(function ($event) {
            $this->assertInstanceOf(ReportingEvent::class, $event);

            return $event;
        });

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->any())->method('isGranted')->willReturn($isGranted);

        return new ReportingService($dispatcher, $security);
    }

    public function testGetAvailableReports(): void
    {
        $sut = $this->getSut();
        $reports = $sut->getAvailableReports(new User());
        self::assertIsArray($reports);
        self::assertEmpty($reports);
    }

    public function testGetAvailableReportsWithPermission(): void
    {
        $sut = $this->getSut(true);
        $reports = $sut->getAvailableReports(new User());
        self::assertIsArray($reports);
        self::assertCount(11, $reports);
    }
}
