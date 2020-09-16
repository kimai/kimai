<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Event\TimesheetCreatePostEvent;
use App\Event\TimesheetCreatePreEvent;
use App\Event\TimesheetDeleteMultiplePreEvent;
use App\Event\TimesheetDeletePreEvent;
use App\Event\TimesheetRestartPostEvent;
use App\Event\TimesheetRestartPreEvent;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use App\Timesheet\TrackingModeService;
use App\Validator\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Timesheet\TimesheetService
 */
class TimesheetServiceTest extends TestCase
{
    private function getSut(?AuthorizationCheckerInterface $authorizationChecker = null, ?EventDispatcherInterface $dispatcher = null): TimesheetService
    {
        $configuration = $this->createMock(TimesheetConfiguration::class);

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getActiveEntries')->willReturn([]);

        $service = new TrackingModeService($configuration, []);
        if ($dispatcher === null) {
            $dispatcher = $this->createMock(EventDispatcherInterface::class);
        }
        if ($authorizationChecker === null) {
            $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        }
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $service = new TimesheetService($configuration, $repository, $service, $dispatcher, $authorizationChecker, $validator);

        return $service;
    }

    public function testCannotSavePersistedTimesheetAsNew()
    {
        $timesheet = $this->createMock(Timesheet::class);
        $timesheet->expects($this->once())->method('getId')->willReturn(1);

        $sut = $this->getSut();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create timesheet, already persisted');

        $sut->saveNewTimesheet($timesheet);
    }

    public function testCannotStartTimesheet()
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $sut = $this->getSut($authorizationChecker);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to start this timesheet record');

        $sut->saveNewTimesheet(new Timesheet());
    }

    public function testCannotRestartedPersistedTimesheet()
    {
        $timesheet = $this->createMock(Timesheet::class);
        $timesheet->expects($this->once())->method('getId')->willReturn(1);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event) {
            self::assertInstanceOf(TimesheetRestartPreEvent::class, $event);
        });

        $sut = $this->getSut(null, $dispatcher);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create timesheet, already persisted');

        $sut->restartTimesheet($timesheet, new Timesheet());
    }

    public function testRestartTimesheetDispatchesTwoEvents()
    {
        $timesheet = $this->createMock(Timesheet::class);
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event) {
            static $counter = 0;
            switch ($counter++) {
                case 0:
                    self::assertInstanceOf(TimesheetRestartPreEvent::class, $event);
                    break;
                case 1:
                    self::assertInstanceOf(TimesheetCreatePreEvent::class, $event);
                    break;
                case 2:
                    self::assertInstanceOf(TimesheetCreatePostEvent::class, $event);
                    break;
                case 3:
                    self::assertInstanceOf(TimesheetRestartPostEvent::class, $event);
                    break;
            }
        });

        $sut = $this->getSut($authorizationChecker, $dispatcher);

        $sut->restartTimesheet($timesheet, new Timesheet());
    }

    public function testPreparePersistedTimesheetAsNew()
    {
        $timesheet = $this->createMock(Timesheet::class);
        $timesheet->expects($this->once())->method('getId')->willReturn(1);

        $sut = $this->getSut();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot prepare timesheet, already persisted');

        $sut->prepareNewTimesheet($timesheet);
    }

    public function testStoppedEntriesCannotBeStoppedAgain()
    {
        $timesheet = new Timesheet();
        $timesheet->setEnd(new \DateTime());

        $sut = $this->getSut();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Timesheet entry already stopped');

        $sut->stopTimesheet($timesheet);
    }

    public function testDeleteDispatchesEvent()
    {
        $timesheet = new Timesheet();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event) use ($timesheet) {
            self::assertInstanceOf(TimesheetDeletePreEvent::class, $event);
            /* @var TimesheetDeletePreEvent $event */
            self::assertSame($timesheet, $event->getTimesheet());
        });

        $sut = $this->getSut(null, $dispatcher);

        $sut->deleteTimesheet($timesheet);
    }

    public function testDeleteMultipleDispatchesEvent()
    {
        $timesheets = [new Timesheet(), new Timesheet()];

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event) use ($timesheets) {
            self::assertInstanceOf(TimesheetDeleteMultiplePreEvent::class, $event);
            /* @var TimesheetDeleteMultiplePreEvent $event */
            self::assertSame($timesheets, $event->getTimesheets());
        });

        $sut = $this->getSut(null, $dispatcher);

        $sut->deleteMultipleTimesheets($timesheets);
    }
}
