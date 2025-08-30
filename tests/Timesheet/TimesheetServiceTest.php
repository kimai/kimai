<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\TimesheetCreatePostEvent;
use App\Event\TimesheetCreatePreEvent;
use App\Event\TimesheetDeleteMultiplePreEvent;
use App\Event\TimesheetDeletePreEvent;
use App\Event\TimesheetRestartPostEvent;
use App\Event\TimesheetRestartPreEvent;
use App\Repository\TimesheetRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Timesheet\TimesheetService;
use App\Timesheet\TrackingModeService;
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(TimesheetService::class)]
class TimesheetServiceTest extends TestCase
{
    private function getSut(
        ?AuthorizationCheckerInterface $authorizationChecker = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?ValidatorInterface $validator = null,
        ?TimesheetRepository $repository = null
    ): TimesheetService {
        $configuration = SystemConfigurationFactory::createStub(['timesheet' => ['active_entries' => ['hard_limit' => 1]]]);

        if ($repository === null) {
            $repository = $this->createMock(TimesheetRepository::class);
            $repository->method('getActiveEntries')->willReturn([]);
        }

        $service = new TrackingModeService($configuration, []);
        if ($dispatcher === null) {
            $dispatcher = $this->createMock(EventDispatcherInterface::class);
        }
        if ($authorizationChecker === null) {
            $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        }
        if ($validator === null) {
            $validator = $this->createMock(ValidatorInterface::class);
            $validator->method('validate')->willReturn(new ConstraintViolationList());
        }

        $service = new TimesheetService($configuration, $repository, $service, $dispatcher, $authorizationChecker, $validator);

        return $service;
    }

    #[Group('legacy')]
    public function testCannotSavePersistedTimesheetAsNew(): void
    {
        $timesheet = $this->createMock(Timesheet::class);
        $timesheet->expects($this->once())->method('getId')->willReturn(1);

        $sut = $this->getSut();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create timesheet, already persisted');

        $sut->saveNewTimesheet($timesheet); // @phpstan-ignore method.deprecated
    }

    public function testCannotStartTimesheet(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $sut = $this->getSut($authorizationChecker);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to start this timesheet record');

        $sut->saveTimesheet(new Timesheet());
    }

    public function testSaveNewTimesheetHasValidationError(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);

        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($constraints);

        $sut = $this->getSut($authorizationChecker, null, $validator);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Validation Failed');

        $sut->saveTimesheet(new Timesheet());
    }

    public function testSaveNewTimesheetStopsActiveRecords(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);

        $timesheet1 = $this->createMock(Timesheet::class);
        $timesheet1->method('getId')->willReturn(1);
        $timesheet1->method('getBegin')->willReturn(new \DateTime());
        $timesheet1->expects($this->once())->method('setBegin');
        $timesheet1->expects($this->once())->method('setEnd');

        $timesheet2 = $this->createMock(Timesheet::class);
        $timesheet2->method('getId')->willReturn(1);
        $timesheet2->method('getBegin')->willReturn(new \DateTime());
        $timesheet2->expects($this->once())->method('setBegin');
        $timesheet2->expects($this->once())->method('setEnd');

        $newTimesheet = new Timesheet();

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getActiveEntries')->willReturn([$newTimesheet, $timesheet1, $timesheet2]);

        $sut = $this->getSut($authorizationChecker, null, null, $repository);

        $sut->saveTimesheet($newTimesheet);
    }

    public function testSaveNewTimesheetFixesTimezone(): void
    {
        $user = new User();
        $user->setTimezone('Europe/Paris');

        $begin = new \DateTime('now', new \DateTimeZone('Africa/Casablanca'));
        $timesheet = new Timesheet();

        $timesheet->setBegin($begin);
        self::assertEquals('Africa/Casablanca', $timesheet->getTimezone());

        $timesheet->setUser($user);
        self::assertEquals('Africa/Casablanca', $timesheet->getTimezone());

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);
        $sut = $this->getSut($authorizationChecker);

        $sut->saveTimesheet($timesheet);

        self::assertEquals('Europe/Paris', $timesheet->getTimezone());
    }

    #[Group('legacy')]
    public function testUpdateTimesheetFixesTimezone(): void
    {
        $user = new User();
        $user->setTimezone('Europe/Paris');

        $begin = new \DateTime('now', new \DateTimeZone('Africa/Casablanca'));
        $timesheet = new Timesheet();

        $timesheet->setBegin($begin);
        self::assertEquals('Africa/Casablanca', $timesheet->getTimezone());

        $timesheet->setUser($user);
        self::assertEquals('Africa/Casablanca', $timesheet->getTimezone());

        $sut = $this->getSut();

        $sut->updateTimesheet($timesheet); // @phpstan-ignore method.deprecated

        self::assertEquals('Europe/Paris', $timesheet->getTimezone());
    }

    public function testCannotRestartedPersistedTimesheet(): void
    {
        $timesheet = $this->createMock(Timesheet::class);
        $timesheet->expects($this->once())->method('getId')->willReturn(1);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event) {
            self::assertInstanceOf(TimesheetRestartPreEvent::class, $event);

            return $event;
        });

        $sut = $this->getSut(null, $dispatcher);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create timesheet, already persisted');

        $sut->restartTimesheet($timesheet, new Timesheet());
    }

    public function testRestartTimesheetDispatchesTwoEvents(): void
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

            return $event;
        });

        $sut = $this->getSut($authorizationChecker, $dispatcher);

        $sut->restartTimesheet($timesheet, new Timesheet());
    }

    public function testPreparePersistedTimesheetAsNew(): void
    {
        $timesheet = $this->createMock(Timesheet::class);
        $timesheet->expects($this->once())->method('getId')->willReturn(1);

        $sut = $this->getSut();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot prepare timesheet, already persisted');

        $sut->prepareNewTimesheet($timesheet);
    }

    public function testStoppedEntriesCannotBeStoppedAgain(): void
    {
        $dateTime = new \DateTime('-2 hours');
        $timesheet = new Timesheet();
        $timesheet->setEnd($dateTime);

        $sut = $this->getSut();

        $sut->stopTimesheet($timesheet);

        self::assertSame($dateTime->getTimestamp(), $timesheet->getEnd()->getTimestamp());
    }

    public function testStopSetsEnd(): void
    {
        $dateTime = new \DateTime('-2 hours');
        $timesheet = new Timesheet();
        $timesheet->setBegin($dateTime);

        $sut = $this->getSut();
        self::assertNull($timesheet->getEnd());

        $sut->stopTimesheet($timesheet);

        self::assertNotNull($timesheet->getEnd());
    }

    public function testDeleteDispatchesEvent(): void
    {
        $timesheet = new Timesheet();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event) use ($timesheet) {
            self::assertInstanceOf(TimesheetDeletePreEvent::class, $event);
            /* @var TimesheetDeletePreEvent $event */
            self::assertSame($timesheet, $event->getTimesheet());

            return $event;
        });

        $sut = $this->getSut(null, $dispatcher);

        $sut->deleteTimesheet($timesheet);
    }

    public function testDeleteMultipleDispatchesEvent(): void
    {
        $timesheets = [new Timesheet(), new Timesheet()];

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function ($event) use ($timesheets) {
            self::assertInstanceOf(TimesheetDeleteMultiplePreEvent::class, $event);
            /* @var TimesheetDeleteMultiplePreEvent $event */
            self::assertSame($timesheets, $event->getTimesheets());

            return $event;
        });

        $sut = $this->getSut(null, $dispatcher);

        $sut->deleteMultipleTimesheets($timesheets);
    }
}
