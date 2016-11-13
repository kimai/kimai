<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Repository;

use AppBundle\Entity\User;
use TimesheetBundle\Entity\Timesheet;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\DBAL\Types\Type;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use TimesheetBundle\Model\Statistic\Month;
use TimesheetBundle\Model\Statistic\Year;
use TimesheetBundle\Model\TimesheetGlobalStatistic;
use TimesheetBundle\Model\TimesheetStatistic;
use DateTime;

/**
 * Class TimesheetRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetRepository extends EntityRepository
{

    /**
     * @param $select
     * @param User|null $user
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function queryThisMonth($select, User $user = null)
    {
        $end = new DateTime('last day of this month');
        $end->setTime(23, 59, 59);
        $begin = new DateTime('first day of this month');
        $begin->setTime(0,0,0);

        return $this->queryTimeRange($select, $begin, $end, $user);
    }

    /**
     * @param $select
     * @param DateTime $begin
     * @param DateTime $end
     * @param User|null $user
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function queryTimeRange($select, DateTime $begin, DateTime $end, User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($select)
            ->from('TimesheetBundle:Timesheet', 't')
            ->where($qb->expr()->gt('t.begin', ':from'))
            ->andWhere($qb->expr()->lt('t.end', ':to'))
            ->setParameter('from', $begin, Type::DATETIME)
            ->setParameter('to', $end, Type::DATETIME);

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb;
    }

    /**
     * Fetch statistic data for one user.
     *
     * @param User $user
     * @return TimesheetStatistic
     */
    public function getUserStatistics(User $user)
    {
        $durationTotal = $this->getEntityManager()
            ->createQuery('SELECT SUM(t.duration) FROM TimesheetBundle:Timesheet t WHERE t.user = :user')
            ->setParameter('user', $user)
            ->getSingleScalarResult();
        $rateTotal = $this->getEntityManager()
            ->createQuery('SELECT SUM(t.rate) FROM TimesheetBundle:Timesheet t WHERE t.user = :user')
            ->setParameter('user', $user)
            ->getSingleScalarResult();
        $amountMonth = $this->queryThisMonth('SUM(t.rate)', $user)
            ->getQuery()
            ->getSingleScalarResult();
        $durationMonth = $this->queryThisMonth('SUM(t.duration)', $user)
            ->getQuery()
            ->getSingleScalarResult();
        $firstEntry = $this->getEntityManager()
            ->createQuery('SELECT MIN(t.begin) FROM TimesheetBundle:Timesheet t WHERE t.user = :user')
            ->setParameter('user', $user)
            ->getSingleScalarResult();

        $stats = new TimesheetStatistic();
        $stats->setAmountTotal($rateTotal);
        $stats->setDurationTotal($durationTotal);
        $stats->setAmountThisMonth($amountMonth);
        $stats->setDurationThisMonth($durationMonth);
        $stats->setFirstEntry(new DateTime($firstEntry));

        return $stats;
    }

    /**
     * Returns an array of Year statistics.
     *
     * @param User|null $user
     * @return Year[]
     */
    public function getMonthlyStats(User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('SUM(t.rate) as totalRate, SUM(t.duration) as totalDuration, MONTH(t.begin) as month, YEAR(t.begin) as year')
            ->from('TimesheetBundle:Timesheet', 't')
            ->where($qb->expr()->gt('t.begin', '0'))
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->orderBy('year', 'DESC')
            ->addOrderBy('month', 'ASC')
            ->groupBy('year')
            ->addGroupBy('month');

        if (null !== $user) {
            $qb->where('t.user = :user')
                ->setParameter('user', $user);
        }

        $years = [];
        foreach($qb->getQuery()->execute() as $statRow) {
            $curYear = $statRow['year'];

            if (!isset($years[$curYear])) {
                $year = new Year($curYear);
                for ($i = 1; $i < 13; $i++) {
                    $month = $i < 10 ? '0' . $i : (string)$i;
                    $year->setMonth(new Month($month));
                }
                $years[$curYear] = $year;
            }

            $month = new Month($statRow['month']);
            $month->setTotalDuration($statRow['totalDuration'])
                ->setTotalRate($statRow['totalRate']);
            $years[$curYear]->setMonth($month);
        }

        return $years;
    }

    /**
     * Fetch statistic data for all user.
     *
     * @return TimesheetStatistic
     */
    public function getGlobalStatistics()
    {
        $durationTotal = $this->getEntityManager()
            ->createQuery('SELECT SUM(t.duration) FROM TimesheetBundle:Timesheet t')
            ->getSingleScalarResult();
        $rateTotal = $this->getEntityManager()
            ->createQuery('SELECT SUM(t.rate) FROM TimesheetBundle:Timesheet t')
            ->getSingleScalarResult();
        $userTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(DISTINCT(t.user)) FROM TimesheetBundle:Timesheet t')
            ->getSingleScalarResult();
        $activeNow = $this->getActiveEntry();
        $amountMonth = $this->queryThisMonth('SUM(t.rate)')
            ->getQuery()
            ->getSingleScalarResult();
        $durationMonth = $this->queryThisMonth('SUM(t.duration)')
            ->getQuery()
            ->getSingleScalarResult();
        $activeMonth = $this->queryThisMonth('COUNT(DISTINCT(t.user))')
            ->getQuery()
            ->getSingleScalarResult();

        $stats = new TimesheetGlobalStatistic();
        $stats->setAmountTotal($rateTotal);
        $stats->setDurationTotal($durationTotal);
        $stats->setActiveTotal($userTotal);
        $stats->setActiveCurrently(count($activeNow));
        $stats->setActiveThisMonth($activeMonth);
        $stats->setAmountThisMonth($amountMonth);
        $stats->setDurationThisMonth($durationMonth);

        return $stats;
    }

    /**
     * @param User $user
     * @return Query
     */
    public function getActiveEntry(User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t')
            ->from('TimesheetBundle:Timesheet', 't')
            ->where($qb->expr()->gt('t.begin', '0'))
            ->andWhere($qb->expr()->isNull('t.end'));

        $params = [];

        if (null !== $user) {
            $qb->andWhere('t.user = :user');
            $params['user'] = $user;
        }

        return $qb->getQuery()->execute($params);
    }

    /**
     * @param User $user
     * @return Query
     */
    public function queryLatest(User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t')
            ->from('TimesheetBundle:Timesheet', 't')
            ->orderBy('t.begin', 'DESC');

        if (null !== $user) {
            $qb->where('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery();
    }

    /**
     * @param User $user
     * @param int $page
     * @return Pagerfanta
     */
    public function findLatest(User $user, $page = 1)
    {
        return $this->getPager($this->queryLatest($user), $page);
    }

    /**
     * @param int $page
     *
     * @return Pagerfanta
     */
    public function findAll($page = 1)
    {
        return $this->getPager($this->queryLatest(), $page);
    }

    /**
     * @param Query $query
     * @param int $page
     * @return Pagerfanta
     */
    protected function getPager(Query $query, $page = 1)
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage(25);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
