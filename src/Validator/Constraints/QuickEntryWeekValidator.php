<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet as TimesheetEntity;
use App\Entity\User;
use App\Model\QuickEntryWeek as QuickEntryWeekModel;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class QuickEntryWeekValidator extends ConstraintValidator
{
    public function __construct(
        private readonly SystemConfiguration $configuration,
        private readonly TimesheetRepository $repository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof QuickEntryWeek) {
            throw new UnexpectedTypeException($constraint, QuickEntryWeek::class);
        }

        if (!\is_object($value) || !($value instanceof QuickEntryWeekModel)) {
            throw new UnexpectedTypeException($value, QuickEntryWeekModel::class);
        }

        foreach ($this->groupSubmittedEntriesByUserAndDay($value) as $entries) {
            $this->validateDay($entries, $constraint);
        }
    }

    /**
     * @return array<string, array<int, array{rowIndex: int, dayIndex: int, timesheet: TimesheetEntity, user: User, begin: \DateTime}>>
     */
    private function groupSubmittedEntriesByUserAndDay(QuickEntryWeekModel $week): array
    {
        $groups = [];

        foreach ($week->getRows() as $rowIndex => $row) {
            $user = $row->getUser();
            foreach ($row->getTimesheets() as $dayIndex => $timesheet) {
                if (!$this->isSubmittedForSave($timesheet)) {
                    continue;
                }

                $begin = $timesheet->getBegin();
                if ($begin === null) {
                    continue;
                }

                $groups[$user->getId() . '_' . $begin->format('Y-m-d')][] = [
                    'rowIndex' => $rowIndex,
                    'dayIndex' => $dayIndex,
                    'timesheet' => $timesheet,
                    'user' => $user,
                    'begin' => $begin,
                ];
            }
        }

        return $groups;
    }

    private function isSubmittedForSave(TimesheetEntity $timesheet): bool
    {
        return $timesheet->getId() === null && $timesheet->getDuration(false) !== null && $timesheet->getBegin() !== null;
    }

    /**
     * @param array<int, array{rowIndex: int, dayIndex: int, timesheet: TimesheetEntity, user: User, begin: \DateTime}> $entries
     */
    private function validateDay(array $entries, QuickEntryWeek $constraint): void
    {
        $user = $entries[0]['user'];
        $factory = DateTimeFactory::createByUser($user);
        $allowOverlap = $this->configuration->isTimesheetAllowOverlappingRecords();

        $dayStart = clone $entries[0]['begin'];
        $dayStart->setTimezone($factory->getTimezone());
        $dayStart->setTime(0, 0, 0);

        $dayEnd = clone $dayStart;
        $dayEnd->modify('+1 day');

        [$maxExistingEnd, $existingSeconds, $hasRunning] = $this->summarizeExistingEntries($user, $dayStart, $dayEnd);

        if ($hasRunning && !$allowOverlap) {
            $this->raiseOverlapViolation($entries);

            return;
        }

        [$newSeconds, $chainEnd] = $allowOverlap
            ? $this->keepPrefilledTimes($entries)
            : $this->chainEntries($entries, $maxExistingEnd ?? $this->defaultBeginOf($dayStart, $factory), $factory);

        $dayLength = $dayEnd->getTimestamp() - $dayStart->getTimestamp();
        $crossesMidnight = $chainEnd !== null && $chainEnd > $dayEnd;

        if (($existingSeconds + $newSeconds) > $dayLength || $crossesMidnight) {
            $this->raiseDayCapacityViolation($entries, $dayStart, $constraint);
        }
    }

    /**
     * @return array{0: \DateTime|null, 1: int, 2: bool}
     */
    private function summarizeExistingEntries(User $user, \DateTime $dayStart, \DateTime $dayEnd): array
    {
        $maxExistingEnd = null;
        $existingSeconds = 0;
        $hasRunning = false;

        foreach ($this->repository->findForDay($user, $dayStart, $dayEnd) as $existing) {
            if ($existing->getEnd() === null) {
                $hasRunning = true;
                continue;
            }
            $existingSeconds += $existing->getDuration(false) ?? 0;
            if ($maxExistingEnd === null || $existing->getEnd() > $maxExistingEnd) {
                $maxExistingEnd = clone $existing->getEnd();
            }
        }

        return [$maxExistingEnd, $existingSeconds, $hasRunning];
    }

    private function defaultBeginOf(\DateTime $dayStart, DateTimeFactory $factory): \DateTime
    {
        $defaultBegin = $factory->createDateTime($this->configuration->getTimesheetDefaultBeginTime());
        $cursor = clone $dayStart;
        $cursor->setTime((int) $defaultBegin->format('H'), (int) $defaultBegin->format('i'), 0);

        return $cursor;
    }

    /**
     * @param array<int, array{rowIndex: int, dayIndex: int, timesheet: TimesheetEntity, user: User, begin: \DateTime}> $entries
     * @return array{0: int, 1: \DateTime|null}
     */
    private function keepPrefilledTimes(array $entries): array
    {
        // pre-fix behaviour: new entries keep their prefilled begin/end, overlaps are not repositioned
        $newSeconds = 0;
        foreach ($entries as $entry) {
            $newSeconds += $entry['timesheet']->getDuration(false) ?? 0;
        }

        return [$newSeconds, null];
    }

    /**
     * @param array<int, array{rowIndex: int, dayIndex: int, timesheet: TimesheetEntity, user: User, begin: \DateTime}> $entries
     * @return array{0: int, 1: \DateTime}
     */
    private function chainEntries(array $entries, \DateTime $cursor, DateTimeFactory $factory): array
    {
        $newSeconds = 0;

        foreach ($entries as $entry) {
            $timesheet = $entry['timesheet'];
            $duration = $timesheet->getDuration(false) ?? 0;
            $newSeconds += $duration;

            $begin = clone $cursor;
            $end = new \DateTime('@' . ($begin->getTimestamp() + $duration));
            $end->setTimezone($factory->getTimezone());

            $timesheet->setBegin($begin);
            $timesheet->setEnd($end);

            $cursor = clone $end;
        }

        return [$newSeconds, $cursor];
    }

    /**
     * @param array<int, array{rowIndex: int, dayIndex: int, timesheet: TimesheetEntity, user: User, begin: \DateTime}> $entries
     */
    private function raiseOverlapViolation(array $entries): void
    {
        foreach ($entries as $entry) {
            $this->context->buildViolation('You already have an entry for this time.')
                ->atPath('rows[' . $entry['rowIndex'] . '].timesheets[' . $entry['dayIndex'] . '].duration')
                ->setTranslationDomain('validators')
                ->setCode(QuickEntryWeek::RECORD_OVERLAPPING)
                ->addViolation();
        }
    }

    /**
     * @param array<int, array{rowIndex: int, dayIndex: int, timesheet: TimesheetEntity, user: User, begin: \DateTime}> $entries
     */
    private function raiseDayCapacityViolation(array $entries, \DateTime $dayStart, QuickEntryWeek $constraint): void
    {
        $offender = $entries[\count($entries) - 1];
        $this->context->buildViolation($constraint->messageDayCapacityExceeded)
            ->atPath('rows[' . $offender['rowIndex'] . '].timesheets[' . $offender['dayIndex'] . '].duration')
            ->setParameter('{{ day }}', $dayStart->format('Y-m-d'))
            ->setTranslationDomain('validators')
            ->setCode(QuickEntryWeek::DAY_CAPACITY_EXCEEDED)
            ->addViolation();
    }
}
