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
use App\Repository\TimesheetRepository;
use App\Repository\WorkingTimeRepository;
use App\Timesheet\DateTimeFactory;
use App\WorkingTime\Model\Day;
use App\WorkingTime\Model\Month;
use App\WorkingTime\Model\Year;

final class WorkingTimeService
{
    public function __construct(private TimesheetRepository $timesheetRepository, private WorkingTimeRepository $workingTimeRepository)
    {
    }

    public function calculateForDay(User $user, \DateTimeInterface $dateTime): WorkingTime
    {
        $result = new WorkingTime($user, $dateTime);
        $result->setExpectedTime($user->getWorkHoursForDay($dateTime));

        // FIXME look up statistics
        // FIXME todo calc

        return $result;
    }

    public function getYear(User $user, \DateTimeInterface $yearDate): Year
    {
        $yearTimes = $this->workingTimeRepository->findForYear($user, $yearDate);
        $existing = [];
        foreach ($yearTimes as $workingTime) {
            $existing[$workingTime->getDate()->format('Y-m-d')] = $workingTime;
        }

        $year = new Year(\DateTimeImmutable::createFromInterface($yearDate));

        $stats = null;

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

                $result = new WorkingTime($user, $day->getDay());
                $result->setExpectedTime($user->getWorkHoursForDay($day->getDay()));

                if (\array_key_exists($key, $stats)) {
                    $result->setActualTime($stats[$key]);
                }

                $day->setWorkingTime($result);
            }
        }

        return $year;
    }

    public function approveYear(Year $year, \DateTimeInterface $approvalDate, User $approver): void
    {
        $update = false;

        foreach ($year->getMonths() as $month) {
            foreach ($month->getDays() as $day) {
                if (!$day instanceof Day || !$month instanceof Month) {
                    continue;
                }

                $workingTime = $day->getWorkingTime();
                if ($workingTime === null) {
                    continue;
                }

                if ($workingTime->getId() === null) {
                    continue;
                }

                if ($month->isLocked() || $workingTime->isApproved()) {
                    continue;
                }

                $workingTime->setApprovedBy($approver);
                $workingTime->setApprovedAt($approvalDate);
                $this->workingTimeRepository->scheduleWorkingTimeUpdate($workingTime);
                $update = true;
            }
        }

        if ($update) {
            $this->workingTimeRepository->persistScheduledWorkingTimes();
        }
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
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->eq('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $user->getId())
            ->addGroupBy('day')
        ;

        $results = $qb->getQuery()->getResult();

        $durations = [];
        foreach ($results as $row) {
            $durations[$row['day']] = (int) $row['duration'];
        }

        return $durations; // @phpstan-ignore-line
    }
}
