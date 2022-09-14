<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Model\DailyStatistic;
use App\Model\MonthlyStatistic;
use App\Repository\TimesheetRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

final class TimesheetStatisticService
{
    /**
     * @var TimesheetRepository
     */
    private $repository;
    private $entityManager;

    public function __construct(TimesheetRepository $repository, EntityManagerInterface $entityManager)
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
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
     * @param DateTime $begin
     * @param DateTime $end
     * @param User[] $users
     * @return array
     */
    public function getDailyStatisticsGrouped(DateTime $begin, DateTime $end, array $users): array
    {
        $stats = [];
        $usersById = [];

        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
            if (!isset($stats[$user->getId()])) {
                $stats[$user->getId()] = [];
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
            $uid = $row['user'];
            $pid = $row['project'];
            $aid = $row['activity'];
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
     * @param DateTime $begin
     * @param DateTime $end
     * @param User[] $users
     * @return array
     */
    public function getMonthlyStatisticsGrouped(DateTime $begin, DateTime $end, array $users): array
    {
        $stats = [];
        $usersById = [];

        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
            if (!isset($stats[$user->getId()])) {
                $stats[$user->getId()] = [];
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
            $uid = $row['user'];
            $pid = $row['project'];
            $aid = $row['activity'];
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

    /**
     * @param DateTime $begin
     * @param DateTime $end
     * @param User[] $users
     * @return array
     */
    public function getGroupedByCustomerProjectActivityUser(DateTime $begin, DateTime $end, array $users): array
    {
        $stats = [];

        $qb = $this->repository->createQueryBuilder('t');
        $qb
            ->select('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internalRate')
            ->addSelect('IDENTITY(t.user) as user')
            ->addSelect('IDENTITY(t.activity) as activity')
            ->addSelect('IDENTITY(t.project) as project')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.begin', ':begin', ':end'))
            ->andWhere($qb->expr()->in('t.user', ':user'))
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('user', $users)
            ->groupBy('project')
            ->addGroupBy('activity')
            ->addGroupBy('user')
        ;

        $results = $qb->getQuery()->getResult();

        $projectIds = [];
        $activityIds = [];
        $userIds = [];

        foreach ($results as $row) {
            $projectId = $row['project'];
            $activityId = $row['activity'];
            $userId = $row['user'];

            $projectIds[$projectId] = $projectId;
            $activityIds[$activityId] = $activityId;
            $userIds[$userId] = $userId;

            if (!isset($stats[$projectId])) {
                $stats[$projectId] = [
                    'id' => $projectId,
                    'customer' => '',
                    'customer_id' => null,
                    'name' => null,
                    'activities' => [],
                    'duration' => 0,
                    'rate' => 0,
                    'internalRate' => 0,
                    'max_users' => 0,
                ];
            }

            $stats[$projectId]['duration'] += (int) $row['duration'];
            $stats[$projectId]['rate'] += (int) $row['rate'];
            $stats[$projectId]['internalRate'] += (int) $row['internalRate'];

            if (!isset($stats[$projectId]['activities'][$activityId])) {
                $stats[$projectId]['activities'][$activityId] = [
                    'id' => $activityId,
                    'name' => null,
                    'users' => [],
                    'duration' => 0,
                    'rate' => 0,
                    'internalRate' => 0,
                ];
            }

            $stats[$projectId]['activities'][$activityId]['duration'] += (int) $row['duration'];
            $stats[$projectId]['activities'][$activityId]['rate'] += (int) $row['rate'];
            $stats[$projectId]['activities'][$activityId]['internalRate'] += (int) $row['internalRate'];

            if (!isset($stats[$projectId]['activities'][$activityId]['users'][$userId])) {
                $stats[$projectId]['activities'][$activityId]['users'][$userId] = [
                    'id' => $userId,
                    'name' => null,
                    'duration' => 0,
                    'rate' => 0,
                    'internalRate' => 0,
                ];
            }

            $stats[$projectId]['activities'][$activityId]['users'][$userId]['duration'] += (int) $row['duration'];
            $stats[$projectId]['activities'][$activityId]['users'][$userId]['rate'] += (int) $row['rate'];
            $stats[$projectId]['activities'][$activityId]['users'][$userId]['internalRate'] += (int) $row['internalRate'];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('a.id, a.name')
            ->from(Activity::class, 'a', 'a.id')
            ->where($qb->expr()->in('a.id', ':id'))
            ->setParameter('id', array_values($activityIds))
        ;
        $activities = $qb->getQuery()->getResult();

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('p.id, p.name, c.id as customer_id, c.name as customer, c.currency')
            ->from(Project::class, 'p', 'p.id')
            ->leftJoin(Customer::class, 'c', Join::WITH, 'c.id = p.customer')
            ->where($qb->expr()->in('p.id', ':id'))
            ->setParameter('id', array_values($projectIds))
        ;
        $projects = $qb->getQuery()->getResult();

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('u')
            ->from(User::class, 'u', 'u.id')
            ->where($qb->expr()->in('u.id', ':id'))
            ->setParameter('id', array_values($userIds))
        ;
        $users = $qb->getQuery()->getResult();

        foreach (array_keys($stats) as $pid) {
            $stats[$pid]['name'] = $projects[$pid]['name'];
            $stats[$pid]['customer'] = $projects[$pid]['customer'];
            $stats[$pid]['customer_id'] = $projects[$pid]['customer_id'];
            foreach (array_keys($stats[$pid]['activities']) as $aid) {
                $stats[$pid]['activities'][$aid]['name'] = $activities[$aid]['name'];
                foreach (array_keys($stats[$pid]['activities'][$aid]['users']) as $uid) {
                    $stats[$pid]['activities'][$aid]['users'][$uid]['name'] = $users[$uid]->getDisplayName();
                }
                $stats[$pid]['max_users'] = max($stats[$pid]['max_users'], \count($stats[$pid]['activities'][$aid]['users']));
            }
        }

        return [
            'stats' => $stats,
            'projects' => $projects,
            'activities' => $activities,
            'users' => $users,
        ];
    }
}
