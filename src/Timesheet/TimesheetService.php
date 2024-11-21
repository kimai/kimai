<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Configuration\SystemConfiguration;
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
use App\Security\AccessDeniedException;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use App\Validator\ValidationException;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TimesheetService
{
    /**
     * @var array<string>
     */
    private array $doNotValidateCodes = [];

    public function __construct(
        private SystemConfiguration $configuration,
        private TimesheetRepository $repository,
        private TrackingModeService $trackingModeService,
        private EventDispatcherInterface $dispatcher,
        private AuthorizationCheckerInterface $auth,
        private ValidatorInterface $validator
    ) {
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

        $timesheet->setBillableMode(Timesheet::BILLABLE_AUTOMATIC);

        return $timesheet;
    }

    /**
     * @param Timesheet $timesheet
     * @param Timesheet $copyFrom
     * @return Timesheet
     * @throws ValidationFailedException for invalid timesheets or running timesheets that should be stopped
     * @throws InvalidArgumentException for already persisted timesheets
     * @throws AccessDeniedException if user is not allowed to start timesheet
     */
    public function restartTimesheet(Timesheet $timesheet, Timesheet $copyFrom): Timesheet
    {
        $this->dispatcher->dispatch(new TimesheetRestartPreEvent($timesheet, $copyFrom));
        $this->saveNewTimesheet($timesheet);
        $this->dispatcher->dispatch(new TimesheetRestartPostEvent($timesheet, $copyFrom));

        return $timesheet;
    }

    /**
     * @throws ValidationFailedException for invalid timesheets or running timesheets that should be stopped
     * @throws InvalidArgumentException for already persisted timesheets
     * @throws AccessDeniedException if user is not allowed to start timesheet
     */
    public function saveNewTimesheet(Timesheet $timesheet): Timesheet
    {
        if (null !== $timesheet->getId()) {
            throw new InvalidArgumentException('Cannot create timesheet, already persisted');
        }

        if (null === $timesheet->getEnd() && !$this->auth->isGranted('start', $timesheet)) {
            throw new AccessDeniedException('You are not allowed to start this timesheet record');
        }

        $this->repository->begin();
        try {
            $this->validateTimesheet($timesheet);
            $this->fixTimezone($timesheet);

            $this->dispatcher->dispatch(new TimesheetCreatePreEvent($timesheet));
            $this->repository->save($timesheet);
            $this->dispatcher->dispatch(new TimesheetCreatePostEvent($timesheet));

            if ($timesheet->isRunning()) {
                try {
                    $this->stopActiveEntries($timesheet);
                } catch (ValidationFailedException $vex) {
                    // could happen for timesheets that were started in the future (end before begin)
                    // or if you try to create a new timesheet while an old one is running for too long
                    throw new ValidationFailedException($vex->getViolations(), 'Cannot stop running timesheet');
                }
            }

            $this->repository->commit();
        } catch (\Exception $ex) {
            $this->repository->rollback();
            throw $ex;
        }

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
        $this->fixTimezone($timesheet);

        $this->dispatcher->dispatch(new TimesheetUpdatePreEvent($timesheet));
        $this->repository->save($timesheet);
        $this->dispatcher->dispatch(new TimesheetUpdatePostEvent($timesheet));

        return $timesheet;
    }

    /**
     * Does NOT validate the given timesheets!
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
     * @param bool $validate
     * @throws ValidationException for already stopped timesheets
     * @throws ValidationFailedException
     */
    public function stopTimesheet(Timesheet $timesheet, bool $validate = true): void
    {
        if (null !== $timesheet->getEnd()) {
            // timesheet already stopped, nothing to do. in previous version, this method did throw a:
            // new ValidationException('Timesheet entry already stopped');
            // but this was removed, because it can happen in the frontend when using multiple tabs/devices and should
            // simply be ignored - showing the message to the user with a "danger status" is not necessary
            return;
        }

        $begin = clone $timesheet->getBegin();
        $now = new \DateTime('now', $begin->getTimezone());

        $timesheet->setBegin($begin);
        $timesheet->setEnd($now);

        if ($validate) {
            $this->validateTimesheet($timesheet);
        }

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
     * @param string[] $groups
     * @throws ValidationFailedException
     */
    public function validateTimesheet(Timesheet $timesheet, array $groups = []): void
    {
        $errors = $this->validator->validate($timesheet, null, $groups);

        if ($errors->count() > 0) {
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                if (\in_array($error->getCode(), $this->doNotValidateCodes, true)) {
                    continue;
                }

                throw new ValidationFailedException($errors, 'Validation Failed');
            }
        }
    }

    /**
     * @param array<string> $validationCodes
     */
    public function setIgnoreValidationCodes(array $validationCodes): void
    {
        $this->doNotValidateCodes = $validationCodes;
    }

    /**
     * Makes sure, that the timesheet record has the timezone of the user.
     *
     * This fixes #1442 and prevents a wrong time if a teamlead edits the
     * timesheet for an employee living in another timezone.
     *
     * @param Timesheet $timesheet
     */
    private function fixTimezone(Timesheet $timesheet)
    {
        if (null !== ($timezone = $timesheet->getTimezone()) && $timezone !== $timesheet->getUser()->getTimezone()) {
            $timesheet->setTimezone($timesheet->getUser()->getTimezone());
        }
    }

    /**
     * Stops active records if more than allowed are running for the timesheet user.
     *
     * The given $timesheet will be ignored and not stopped (assuming it is the latest one that was re-started).
     *
     * @param Timesheet $timesheet
     * @return int
     * @throws ValidationException
     * @throws ValidationFailedException
     */
    private function stopActiveEntries(Timesheet $timesheet): int
    {
        $hardLimit = $this->configuration->getTimesheetActiveEntriesHardLimit();
        $activeEntries = $this->repository->getActiveEntries($timesheet->getUser());

        if (empty($activeEntries)) {
            return 0;
        }

        $activeEntries = array_reverse($activeEntries);
        $needsStop = \count($activeEntries) - $hardLimit;
        $counter = 0;

        foreach ($activeEntries as $activeEntry) {
            if ($timesheet->getId() !== $activeEntry->getId() && $needsStop > 0) {
                $this->stopTimesheet($activeEntry);
                $needsStop--;
                $counter++;
            }
        }

        return $counter;
    }

    public function getActiveTrackingMode(): TrackingModeInterface
    {
        return $this->trackingModeService->getActiveMode();
    }

    public function stopAll(): int
    {
        $activeEntries = $this->repository->getActiveEntries();
        $counter = 0;

        foreach ($activeEntries as $timesheet) {
            $this->stopTimesheet($timesheet, false);
            $counter++;
        }

        return $counter;
    }
}
