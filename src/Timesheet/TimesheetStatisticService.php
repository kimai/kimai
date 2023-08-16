<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\User;
use App\Model\DailyStatistic;
use App\Model\MonthlyStatistic;
use App\Repository\TimesheetRepository;
use DateTime;
use DateTimeInterface;

final class TimesheetStatisticService
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    /**
     * @param DateTimeInterface $begin
     * @param DateTimeInterface $end
     * @param User[] $users
     * @return DailyStatistic[]
     */
    public function getDailyStatistics(DateTimeInterface $begin, DateTimeInterface $end, array $users): array
    {
        /** @var DailyStatistic[] $stats */
        $stats = [];

        foreach ($users as $user) {
            if (!isset($stats[$user->getId()])) {
                $stats[$user->getId()] = new DailyStatistic($begin, $end, $user);
            }
        }

        $qb = $this->repository->createQueryBuilder('t');

        $qb
            ->select('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('t.billable as billable')
            ->addSelect('IDENTITY(t.user) as user')
            ->addSelect('DAY(t.date) as day')
            ->addSelect('MONTH(t.date) as month')
            ->addSelect('YEAR(t.date) as year')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->in('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $users)
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('day')
            ->addGroupBy('user')
            ->addGroupBy('billable')
        ;

        $results = $qb->getQuery()->getResult();

        foreach ($results as $row) {
            $day = $stats[$row['user']]->getDay($row['year'], $row['month'], $row['day']);

            if ($day === null) {
                // timezone differences
                continue;
            }

            $day->setTotalDuration($day->getTotalDuration() + (int) $row['duration']);
            $day->setTotalRate($day->getTotalRate() + (float) $row['rate']);
            $day->setTotalInternalRate($day->getTotalInternalRate() + (float) $row['internalRate']);
            if ($row['billable']) {
                $day->setBillableRate((float) $row['rate']);
                $day->setBillableDuration((int) $row['duration']);
            }
        }

        return array_values($stats);
    }

    /**
     * @internal only for core development
     * @param DateTimeInterface $begin
     * @param DateTimeInterface $end
     * @param User[] $users
     * @return array
     */
    public function getDailyStatisticsGrouped(DateTimeInterface $begin, DateTimeInterface $end, array $users): array
    {
        $stats = [];
        $usersById = [];

        foreach ($users as $user) {
            $uid = (string) $user->getId();
            $usersById[$uid] = $user;
            if (!isset($stats[$uid])) {
                $stats[$uid] = [];
            }
        }

        $qb = $this->repository->createQueryBuilder('t');
        $qb
            ->select('COALESCE(SUM(t.rate), 0.0) as rate')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('t.billable as billable')
            ->addSelect('IDENTITY(t.user) as user')
            ->addSelect('IDENTITY(t.project) as project')
            ->addSelect('IDENTITY(t.activity) as activity')
            ->addSelect('DATE(t.date) as date')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->in('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $users)
            ->groupBy('date')
            ->addGroupBy('project')
            ->addGroupBy('activity')
            ->addGroupBy('user')
            ->addGroupBy('billable')
        ;

        $results = $qb->getQuery()->getResult();

        foreach ($results as $row) {
            $uid = (string) $row['user'];
            $pid = (string) $row['project'];
            $aid = (string) $row['activity'];
            if (!isset($stats[$uid][$pid])) {
                $stats[$uid][$pid] = ['project' => $pid, 'activities' => []];
            }
            if (!isset($stats[$uid][$pid]['activities'][$aid])) {
                $stats[$uid][$pid]['activities'][$aid] = ['activity' => $aid, 'data' => new DailyStatistic($begin, $end, $usersById[$uid])];
            }

            /** @var DailyStatistic $days */
            $days = $stats[$uid][$pid]['activities'][$aid]['data'];
            $day = $days->getDayByReportDate($row['date']);

            if ($day === null) {
                // timezone differences
                continue;
            }

            $day->setTotalDuration($day->getTotalDuration() + (int) $row['duration']);
            $day->setTotalRate($day->getTotalRate() + (float) $row['rate']);
            $day->setTotalInternalRate($day->getTotalInternalRate() + (float) $row['internalRate']);
            if ($row['billable']) {
                $day->setBillableRate((float) $row['rate']);
                $day->setBillableDuration((int) $row['duration']);
            }
        }

        return $stats;
    }

    /**
     * @internal only for core development
     * @param DateTimeInterface $begin
     * @param DateTimeInterface $end
     * @param User[] $users
     * @return array
     */
    public function getMonthlyStatisticsGrouped(DateTimeInterface $begin, DateTimeInterface $end, array $users): array
    {
        $stats = [];
        $usersById = [];

        foreach ($users as $user) {
            $uid = (string) $user->getId();
            $usersById[$uid] = $user;
            if (!isset($stats[$uid])) {
                $stats[$uid] = [];
            }
        }

        $qb = $this->repository->createQueryBuilder('t');
        $qb
            ->select('COALESCE(SUM(t.rate), 0.0) as rate')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('t.billable as billable')
            ->addSelect('IDENTITY(t.user) as user')
            ->addSelect('IDENTITY(t.project) as project')
            ->addSelect('IDENTITY(t.activity) as activity')
            ->addSelect('YEAR(t.date) as year')
            ->addSelect('MONTH(t.date) as month')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->in('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $users)
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('project')
            ->addGroupBy('activity')
            ->addGroupBy('user')
            ->addGroupBy('billable')
        ;

        $results = $qb->getQuery()->getResult();

        foreach ($results as $row) {
            $uid = (string) $row['user'];
            $pid = (string) $row['project'];
            $aid = (string) $row['activity'];
            if (!isset($stats[$uid][$pid])) {
                $stats[$uid][$pid] = ['project' => $pid, 'activities' => []];
            }
            if (!isset($stats[$uid][$pid]['activities'][$aid])) {
                $stats[$uid][$pid]['activities'][$aid] = ['activity' => $aid, 'data' => new MonthlyStatistic($begin, $end, $usersById[$uid])];
            }

            /** @var MonthlyStatistic $months */
            $months = $stats[$uid][$pid]['activities'][$aid]['data'];
            $month = $months->getMonth((string) $row['year'], (string) $row['month']);

            if ($month === null) {
                // timezone differences
                continue;
            }

            $month->setTotalDuration($month->getTotalDuration() + (int) $row['duration']);
            $month->setTotalRate($month->getTotalRate() + (float) $row['rate']);
            $month->setTotalInternalRate($month->getTotalInternalRate() + (float) $row['internalRate']);
            if ($row['billable']) {
                $month->setBillableRate((float) $row['rate']);
                $month->setBillableDuration((int) $row['duration']);
            }
        }

        return $stats;
    }

    public function findFirstRecordDate(User $user): ?DateTime
    {
        $result = $this->repository->createQueryBuilder('t')
            ->select('MIN(t.begin)')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        if ($result === null) {
            return null;
        }

        return new DateTime((string) $result, new \DateTimeZone($user->getTimezone()));
    }

    /**
     * Returns an array of Year statistics.
     *
     * @param DateTime $begin
     * @param DateTime $end
     * @param User[] $users
     * @return MonthlyStatistic[]
     */
    public function getMonthlyStats(DateTime $begin, DateTime $end, array $users): array
    {
        /** @var MonthlyStatistic[] $stats */
        $stats = [];

        foreach ($users as $user) {
            if (!isset($stats[$user->getId()])) {
                $stats[$user->getId()] = new MonthlyStatistic($begin, $end, $user);
            }
        }

        $qb = $this->repository->createQueryBuilder('t');
        $qb
            ->select('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('t.billable as billable')
            ->addSelect('MONTH(t.date) as month')
            ->addSelect('YEAR(t.date) as year')
            ->addSelect('IDENTITY(t.user) as user')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->in('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $users)
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('user')
            ->addGroupBy('billable')
        ;

        $results = $qb->getQuery()->getResult();

        foreach ($results as $row) {
            $month = $stats[$row['user']]->getMonth($row['year'], $row['month']);

            if ($month === null) {
                // might happen for the last month, which is accidentally queried due to timezone differences
                continue;
            }

            $month->setTotalDuration($month->getTotalDuration() + (int) $row['duration']);
            $month->setTotalRate($month->getTotalRate() + (float) $row['rate']);
            $month->setTotalInternalRate($month->getTotalInternalRate() + (float) $row['internalRate']);
            if ($row['billable']) {
                $month->setBillableRate((float) $row['rate']);
                $month->setBillableDuration((int) $row['duration']);
            }
        }

        return array_values($stats);
    }
}
