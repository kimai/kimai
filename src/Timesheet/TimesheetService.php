<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\TimesheetCreatePostEvent;
use App\Event\TimesheetCreatePreEvent;
use App\Event\TimesheetDeleteMultiplePreEvent;
use App\Event\TimesheetDeletePreEvent;
use App\Event\TimesheetMetaDefinitionEvent;
use App\Event\TimesheetStopPostEvent;
use App\Event\TimesheetStopPreEvent;
use App\Event\TimesheetUpdateMultiplePostEvent;
use App\Event\TimesheetUpdateMultiplePreEvent;
use App\Event\TimesheetUpdatePostEvent;
use App\Event\TimesheetUpdatePreEvent;
use App\Repository\TimesheetRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class TimesheetService
{
    /**
     * @var TimesheetRepository
     */
    private $repository;
    /**
     * @var TimesheetConfiguration
     */
    private $configuration;
    /**
     * @var TrackingModeService
     */
    private $trackingModeService;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $auth;

    public function __construct(
        TimesheetConfiguration $configuration,
        TimesheetRepository $repository,
        TrackingModeService $service,
        EventDispatcherInterface $dispatcher,
        AuthorizationCheckerInterface $security
    ) {
        $this->configuration = $configuration;
        $this->repository = $repository;
        $this->trackingModeService = $service;
        $this->dispatcher = $dispatcher;
        $this->auth = $security;
    }

    /**
     * Calls prepareNewTimesheet() automatically if $request is not null.
     *
     * @param User $user
     * @param Request|null $request
     * @return Timesheet
     */
    public function createNewTimesheet(User $user, ?Request $request = null): Timesheet
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($user);

        if (null !== $request) {
            $this->prepareNewTimesheet($timesheet, $request);
        }

        return $timesheet;
    }

    public function prepareNewTimesheet(Timesheet $timesheet, ?Request $request = null): Timesheet
    {
        if (null !== $timesheet->getId()) {
            throw new \InvalidArgumentException('Cannot prepare timesheet, already persisted');
        }

        $event = new TimesheetMetaDefinitionEvent($timesheet);
        $this->dispatcher->dispatch($event);

        $mode = $this->trackingModeService->getActiveMode();
        $mode->create($timesheet, $request);

        return $timesheet;
    }

    public function saveNewTimesheet(Timesheet $timesheet): Timesheet
    {
        if (null !== $timesheet->getId()) {
            throw new \InvalidArgumentException('Cannot create timesheet, already persisted');
        }

        if (null === $timesheet->getEnd() && !$this->auth->isGranted('start', $timesheet)) {
            throw new AccessDeniedHttpException('You are not allowed to start this timesheet record');
        }

        $this->dispatcher->dispatch(new TimesheetCreatePreEvent($timesheet));
        $this->repository->add($timesheet, $this->configuration->getActiveEntriesHardLimit());
        $this->dispatcher->dispatch(new TimesheetCreatePostEvent($timesheet));

        return $timesheet;
    }

    public function updateTimesheet(Timesheet $timesheet): Timesheet
    {
        $this->dispatcher->dispatch(new TimesheetUpdatePreEvent($timesheet));
        $this->repository->save($timesheet);
        $this->dispatcher->dispatch(new TimesheetUpdatePostEvent($timesheet));

        return $timesheet;
    }

    public function updateMultipleTimesheets(array $timesheets): array
    {
        $this->dispatcher->dispatch(new TimesheetUpdateMultiplePreEvent($timesheets));
        $this->repository->saveMultiple($timesheets);
        $this->dispatcher->dispatch(new TimesheetUpdateMultiplePostEvent($timesheets));

        return $timesheets;
    }

    public function stopTimesheet(Timesheet $timesheet): void
    {
        $this->dispatcher->dispatch(new TimesheetStopPreEvent($timesheet));
        $this->repository->stopRecording($timesheet);
        $this->dispatcher->dispatch(new TimesheetStopPostEvent($timesheet));
    }

    public function deleteTimesheet(Timesheet $timesheet): void
    {
        $this->dispatcher->dispatch(new TimesheetDeletePreEvent($timesheet));
        $this->repository->delete($timesheet);
    }

    public function deleteMultipleTimesheets(array $timesheets): void
    {
        $this->dispatcher->dispatch(new TimesheetDeleteMultiplePreEvent($timesheets));
        $this->repository->deleteMultiple($timesheets);
    }
}
