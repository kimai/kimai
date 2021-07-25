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

final class TimesheetStatisticService
{
    /**
     * @var TimesheetRepository
     */
    private $repository;

    public function __construct(TimesheetRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param DateTime $begin
     * @param DateTime $end
     * @param User[] $users
     * @return DailyStatistic[]
     */
    public function getDailyStatistics(DateTime $begin, DateTime $end, array $users): array
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
            ->addSelect('DAY(t.begin) as day')
            ->addSelect('MONTH(t.begin) as month')
            ->addSelect('YEAR(t.begin) as year')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->in('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $users)
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('day')
            ->addGroupBy('billable')
            ->addGroupBy('user')
        ;

        $results = $qb->getQuery()->getResult();

        foreach ($results as $row) {
            $day = $stats[$row['user']]->getDay($row['year'], $row['month'], $row['day']);
            if ($day === null) {
                // might happen for the last day, which is accidentally queried due to timezone differences
                continue;
            }
            $day->setTotalDuration($day->getTotalDuration() + (int) $row['duration']);
            $day->setTotalRate($day->getTotalRate() + (float) $row['duration']);
            $day->setTotalInternalRate($day->getTotalInternalRate() + (float) $row['duration']);
            if ($row['billable']) {
                $day->setBillableRate((float) $row['duration']);
                $day->setBillableDuration((int) $row['duration']);
            }
        }

        return array_values($stats);
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

        return new DateTime($result, new \DateTimeZone($user->getTimezone()));
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
            ->addSelect('MONTH(t.begin) as month')
            ->addSelect('YEAR(t.begin) as year')
            ->addSelect('IDENTITY(t.user) as user')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->in('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $users)
            ->groupBy('year')
            ->addGroupBy('month')
            ->addGroupBy('billable')
            ->addGroupBy('user')
        ;

        $results = $qb->getQuery()->getResult();

        foreach ($results as $row) {
            $month = $stats[$row['user']]->getMonth($row['year'], $row['month']);
            if ($month === null) {
                // might happen for the last month, which is accidentally queried due to timezone differences
                continue;
            }
            $month->setTotalDuration($month->getTotalDuration() + (int) $row['duration']);
            $month->setTotalRate($month->getTotalRate() + (float) $row['duration']);
            $month->setTotalInternalRate($month->getTotalInternalRate() + (float) $row['duration']);
            if ($row['billable']) {
                $month->setBillableRate((float) $row['duration']);
                $month->setBillableDuration((int) $row['duration']);
            }
        }

        return array_values($stats);
    }
}
