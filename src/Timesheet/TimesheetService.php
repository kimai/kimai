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
use App\Event\TimesheetRestartPostEvent;
use App\Event\TimesheetRestartPreEvent;
use App\Event\TimesheetStopPostEvent;
use App\Event\TimesheetStopPreEvent;
use App\Event\TimesheetUpdateMultiplePostEvent;
use App\Event\TimesheetUpdateMultiplePreEvent;
use App\Event\TimesheetUpdatePostEvent;
use App\Event\TimesheetUpdatePreEvent;
use App\Repository\TimesheetRepository;
use App\Validator\ValidationException;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        TimesheetConfiguration $configuration,
        TimesheetRepository $repository,
        TrackingModeService $service,
        EventDispatcherInterface $dispatcher,
        AuthorizationCheckerInterface $security,
        ValidatorInterface $validator
    ) {
        $this->configuration = $configuration;
        $this->repository = $repository;
        $this->trackingModeService = $service;
        $this->dispatcher = $dispatcher;
        $this->auth = $security;
        $this->validator = $validator;
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
            throw new InvalidArgumentException('Cannot prepare timesheet, already persisted');
        }

        $event = new TimesheetMetaDefinitionEvent($timesheet);
        $this->dispatcher->dispatch($event);

        $mode = $this->trackingModeService->getActiveMode();
        $mode->create($timesheet, $request);

        return $timesheet;
    }

    /**
     * @param Timesheet $timesheet
     * @param Timesheet $copyFrom
     * @throws ValidationFailedException for invalid timesheets or running timesheets that should be stopped
     * @throws InvalidArgumentException for already persisted timesheets
     * @throws AccessDeniedHttpException if user is not allowed to start timesheet
     */
    public function restartTimesheet(Timesheet $timesheet, Timesheet $copyFrom): Timesheet
    {
        $this->dispatcher->dispatch(new TimesheetRestartPreEvent($timesheet, $copyFrom));
        $this->saveNewTimesheet($timesheet);
        $this->dispatcher->dispatch(new TimesheetRestartPostEvent($timesheet, $copyFrom));

        return $timesheet;
    }

    /**
     * @param Timesheet $timesheet
     * @return Timesheet
     * @throws ValidationFailedException for invalid timesheets or running timesheets that should be stopped
     * @throws InvalidArgumentException for already persisted timesheets
     * @throws AccessDeniedHttpException if user is not allowed to start timesheet
     */
    public function saveNewTimesheet(Timesheet $timesheet): Timesheet
    {
        if (null !== $timesheet->getId()) {
            throw new InvalidArgumentException('Cannot create timesheet, already persisted');
        }

        if (null === $timesheet->getEnd() && !$this->auth->isGranted('start', $timesheet)) {
            throw new AccessDeniedHttpException('You are not allowed to start this timesheet record');
        }

        $this->validateTimesheet($timesheet);

        try {
            $this->stopActiveEntries($timesheet);
        } catch (ValidationFailedException $vex) {
            // could happen for timesheets that were started in the future (end before begin)
            throw new ValidationFailedException($vex->getViolations(), 'Cannot stop running timesheet');
        }

        $this->dispatcher->dispatch(new TimesheetCreatePreEvent($timesheet));
        $this->repository->save($timesheet);
        $this->dispatcher->dispatch(new TimesheetCreatePostEvent($timesheet));

        return $timesheet;
    }

    /**
     * Does NOT validate the given timesheet!
     *
     * @param Timesheet $timesheet
     * @return Timesheet
     * @throws \Exception
     */
    public function updateTimesheet(Timesheet $timesheet): Timesheet
    {
        // there is at least one edge case which leads to a problem:
        // if you do not allow overlapping entries, you cannot restart a timesheet by removing the
        // end date if another timesheet is running, because the check for existing timesheets will always trigger
        /*
        if ($timesheet->getEnd() === null) {
            $this->stopActiveEntries($timesheet);
        }
        */

        $this->dispatcher->dispatch(new TimesheetUpdatePreEvent($timesheet));
        $this->repository->save($timesheet);
        $this->dispatcher->dispatch(new TimesheetUpdatePostEvent($timesheet));

        return $timesheet;
    }

    /**
     * Does NOT validate the given timesheet!
     *
     * @param array $timesheets
     * @return array
     * @throws \Exception
     */
    public function updateMultipleTimesheets(array $timesheets): array
    {
        $this->dispatcher->dispatch(new TimesheetUpdateMultiplePreEvent($timesheets));
        $this->repository->saveMultiple($timesheets);
        $this->dispatcher->dispatch(new TimesheetUpdateMultiplePostEvent($timesheets));

        return $timesheets;
    }

    /**
     * Validates the given timesheet, especially important for not setting a wrong end date.
     * But also to check that all required data is set.
     *
     * @param Timesheet $timesheet
     * @throws ValidationException for already stopped timesheets
     * @throws ValidationFailedException
     */
    public function stopTimesheet(Timesheet $timesheet): void
    {
        if (null !== $timesheet->getEnd()) {
            throw new ValidationException('Timesheet entry already stopped');
        }

        $begin = clone $timesheet->getBegin();
        $now = new \DateTime('now', $begin->getTimezone());

        $timesheet->setBegin($begin);
        $timesheet->setEnd($now);

        $this->validateTimesheet($timesheet);

        $this->dispatcher->dispatch(new TimesheetStopPreEvent($timesheet));
        $this->repository->save($timesheet);
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

    /**
     * @param Timesheet $timesheet
     * @param string[] $groups
     * @throws ValidationFailedException
     */
    private function validateTimesheet(Timesheet $timesheet, array $groups = []): void
    {
        $errors = $this->validator->validate($timesheet, null, $groups);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors, 'Validation Failed');
        }
    }

    /**
     * Stops all active records for the current user, besides the given $timesheet.
     *
     * @param Timesheet $timesheet
     * @return int
     * @throws ValidationException
     * @throws ValidationFailedException
     */
    private function stopActiveEntries(Timesheet $timesheet): int
    {
        $user = $timesheet->getUser();
        $hardLimit = $this->configuration->getActiveEntriesHardLimit();
        $activeEntries = $this->repository->getActiveEntries($user);
        $counter = 0;

        // reduce limit by one:
        // this method is only called when a new entry is started
        // -> all entries, including the new one must not exceed the $limit
        $limit = $hardLimit - 1;

        if (\count($activeEntries) > $limit) {
            $i = 1;
            foreach ($activeEntries as $activeEntry) {
                if ($i > $limit && $timesheet->getId() !== $activeEntry->getId()) {
                    if ($hardLimit > 1) {
                        throw new ValidationException('timesheet.start.exceeded_limit');
                    }

                    $this->stopTimesheet($activeEntry);
                    $counter++;
                }
                $i++;
            }
        }

        return $counter;
    }
}
