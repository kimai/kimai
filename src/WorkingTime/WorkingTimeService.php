<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime;

use App\Entity\User;
use App\Entity\WorkingTime;
use App\Event\WorkingTimeApproveMonthEvent;
use App\Event\WorkingTimeYearEvent;
use App\Event\WorkingTimeYearSummaryEvent;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Repository\WorkingTimeRepository;
use App\Timesheet\DateTimeFactory;
use App\WorkingTime\Mode\WorkingTimeMode;
use App\WorkingTime\Mode\WorkingTimeModeFactory;
use App\WorkingTime\Model\Month;
use App\WorkingTime\Model\Year;
use App\WorkingTime\Model\YearPerUserSummary;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal this API and the entire namespace is experimental: expect changes!
 */
final class WorkingTimeService
{
    private const LATEST_APPROVAL_PREF = '_latest_approval';
    private const LATEST_APPROVAL_FORMAT = 'Y-m-d H:i:s';
    /** @var array<string, WorkingTime|null> */
    private array $latestApprovals = [];

    public function __construct(
        private readonly TimesheetRepository $timesheetRepository,
        private readonly WorkingTimeRepository $workingTimeRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly WorkingTimeModeFactory $contractModeService,
        private readonly UserRepository $userRepository,
    )
    {
    }

    public function getContractMode(User $user): WorkingTimeMode
    {
        return $this->contractModeService->getModeForUser($user);
    }

    public function getYearSummary(Year $year, \DateTimeInterface $until): YearPerUserSummary
    {
        $yearPerUserSummary = new YearPerUserSummary($year);

        $summaryEvent = new WorkingTimeYearSummaryEvent($yearPerUserSummary, $until);
        $this->eventDispatcher->dispatch($summaryEvent);

        return $yearPerUserSummary;
    }

    /**
     * @deprecated since 2.25.0 - kept for BC with old plugin versions
     */
    public function getLatestApproval(User $user): ?WorkingTime
    {
        if ($user->getId() === null) {
            return null;
        }

        $key = 'u_' . $user->getId();

        if (!\array_key_exists($key, $this->latestApprovals)) {
            $this->latestApprovals[$key] = $this->workingTimeRepository->getLatestApproval($user);
        }

        return $this->latestApprovals[$key];
    }

    public function getLatestApprovalDate(User $user): ?\DateTimeInterface
    {
        if ($user->getId() === null) {
            return null;
        }

        $date = $user->getPreferenceValue(self::LATEST_APPROVAL_PREF, false);

        // false means = there is no setting existing: let's calculate it
        // null means = there is no approval existing yet
        if ($date === false) {
            $date = $this->workingTimeRepository->getLatestApprovalDate($user);

            // let's store the approval always: we can later detect if an approval exists
            // or not based on the existence of the preference, which saves DB queries
            $value = ($date !== null) ? $date->format(self::LATEST_APPROVAL_FORMAT) : null;
            $user->setPreferenceValue(self::LATEST_APPROVAL_PREF, $value);
            $this->userRepository->saveUser($user);

            return $date;
        }

        if (\is_string($date)) {
            return new \DateTimeImmutable($date, new \DateTimeZone($user->getTimezone()));
        }

        return null;
    }

    public function isApproved(User $user, \DateTimeInterface $dateTime): bool
    {
        $latestApprovalDate = $this->getLatestApprovalDate($user);
        if ($latestApprovalDate === null) {
            return false;
        }

        $begin = \DateTimeImmutable::createFromInterface($dateTime);
        $begin = $begin->setTime(0, 0, 0);

        if ($begin > $latestApprovalDate) {
            return false;
        }

        return true;
    }

    public function getYear(User $user, \DateTimeInterface $yearDate, \DateTimeInterface $until): Year
    {
        $yearTimes = $this->workingTimeRepository->findForYear($user, $yearDate);
        $existing = [];
        foreach ($yearTimes as $workingTime) {
            $existing[$workingTime->getDate()->format('Y-m-d')] = $workingTime;
        }

        $year = new Year(\DateTimeImmutable::createFromInterface($yearDate), $user);

        $stats = null;
        $firstDay = $user->getWorkStartingDay();
        $lastDay = $user->getLastWorkingDay();
        $calculator = $this->getContractMode($user)->getCalculator($user);

        foreach ($year->getMonths() as $month) {
            foreach ($month->getDays() as $day) {
                $key = $day->getDay()->format('Y-m-d');
                if (\array_key_exists($key, $existing)) {
                    $day->setWorkingTime($existing[$key]);
                    continue;
                }

                if ($stats === null) {
                    $stats = $this->getYearStatistics($yearDate, $user);
                }

                $dayDate = $day->getDay();
                $result = new WorkingTime($user, $dayDate);

                if (($firstDay === null || $firstDay <= $dayDate) && ($lastDay === null || $lastDay >= $dayDate)) {
                    $result->setExpectedTime($calculator->getWorkHoursForDay($dayDate));
                }

                if (\array_key_exists($key, $stats)) {
                    $result->setActualTime($stats[$key]);
                }

                $day->setWorkingTime($result);
            }
        }

        $event = new WorkingTimeYearEvent($year, $until);
        $this->eventDispatcher->dispatch($event);

        return $year;
    }

    public function getMonth(User $user, \DateTimeInterface $monthDate, \DateTimeInterface $until): Month
    {
        // uses the year, because that triggers the required events to collect all different working times
        $year = $this->getYear($user, $monthDate, $until);

        return $year->getMonth($monthDate);
    }

    public function approveMonth(User $user, Month $month, \DateTimeInterface $approvalDate, User $approvedBy): void
    {
        foreach ($month->getDays() as $day) {
            $workingTime = $day->getWorkingTime();
            if ($workingTime === null) {
                continue;
            }

            if ($workingTime->getId() !== null) {
                continue;
            }

            if ($month->isLocked() || $workingTime->isApproved()) {
                continue;
            }

            $workingTime->setApprovedBy($approvedBy);
            // FIXME see calling method
            $workingTime->setApprovedAt(\DateTimeImmutable::createFromInterface($approvalDate));
            $this->workingTimeRepository->scheduleWorkingTimeUpdate($workingTime);
        }

        $this->workingTimeRepository->persistScheduledWorkingTimes();

        $user->setPreferenceValue(self::LATEST_APPROVAL_PREF, $this->workingTimeRepository->getLatestApprovalDate($user)?->format(self::LATEST_APPROVAL_FORMAT));
        $this->userRepository->saveUser($user);

        $this->eventDispatcher->dispatch(new WorkingTimeApproveMonthEvent($user, $month, $approvalDate, $approvedBy));
    }

    /**
     * @param \DateTimeInterface $year
     * @param User $user
     * @return array<string, int>
     */
    private function getYearStatistics(\DateTimeInterface $year, User $user): array
    {
        $dateTimeFactory = DateTimeFactory::createByUser($user);
        $begin = $dateTimeFactory->createStartOfYear($year);
        $end = $dateTimeFactory->createEndOfYear($year);

        $qb = $this->timesheetRepository->createQueryBuilder('t');

        $qb
            ->select('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('DATE(t.date) as day')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.date', ':begin', ':end'))
            ->andWhere($qb->expr()->eq('t.user', ':user'))
            ->setParameter('begin', $begin->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->setParameter('user', $user->getId())
            ->addGroupBy('day')
        ;

        $results = $qb->getQuery()->getResult();

        $durations = [];
        foreach ($results as $row) {
            $durations[$row['day']] = (int) $row['duration'];
        }

        return $durations;
    }
}
