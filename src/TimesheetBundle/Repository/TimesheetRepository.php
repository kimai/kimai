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
use TimesheetBundle\Entity\Activity;
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
use TimesheetBundle\Repository\Query\TimesheetQuery;

/**
 * Class TimesheetRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetRepository extends EntityRepository
{

    /**
     * @param Timesheet $entry
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function stopRecording(Timesheet $entry)
    {
        $end = new DateTime();
        $begin = $entry->getBegin();

        $entry->setEnd($end);
        $entry->setDuration($end->getTimestamp() - $begin->getTimestamp());

        // TODO calculate rate by users hourly rate

        $entityManager = $this->getEntityManager();
        $entityManager->persist($entry);
        $entityManager->flush();

        return true;
    }

    /**
     * @param User $user
     * @param Activity $activity
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function startRecording(User $user, Activity $activity)
    {
        $entry = new Timesheet();
        $entry
            ->setBegin(new DateTime())
            ->setUser($user)
            ->setActivity($activity);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($entry);
        $entityManager->flush();

        return true;
    }

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
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     * @return TimesheetGlobalStatistic
     * @throws \Doctrine\ORM\NonUniqueResultException
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
        $activeNow = $this->getActiveEntries();
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
     * TODO replace me by a findByQuery() call
     *
     * @param User $user
     * @return Timesheet[]|null
     */
    public function getActiveEntries(User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t', 'a', 'p', 'c')
            ->from('TimesheetBundle:Timesheet', 't')
            ->join('t.activity', 'a')
            ->join('a.project', 'p')
            ->join('p.customer', 'c')
            ->where($qb->expr()->gt('t.begin', '0'))
            ->andWhere($qb->expr()->isNull('t.end'))
            ->orderBy('t.begin', 'DESC');

        $params = [];

        if (null !== $user) {
            $qb->andWhere('t.user = :user');
            $params['user'] = $user;
        }

        return $qb->getQuery()->execute($params);
    }

    /**
     * @param TimesheetQuery $query
     * @return Pagerfanta
     */
    public function findByQuery(TimesheetQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t', 'a', 'p', 'c')
            ->from('TimesheetBundle:Timesheet', 't')
            ->join('t.activity', 'a')
            ->join('a.project', 'p')
            ->join('p.customer', 'c')
            ->orderBy('t.' . $query->getOrderBy(), $query->getOrder());

        if ($query->getUser() !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $query->getUser());
        }

        if ($query->getState() == TimesheetQuery::STATE_RUNNING) {
            $qb->andWhere($qb->expr()->isNull('t.end'));
        } elseif ($query->getState() == TimesheetQuery::STATE_STOPPED) {
            $qb->andWhere($qb->expr()->isNotNull('t.end'));
        }

        if ($query->getActivity() !== null) {
            $qb->andWhere('t.activity = :activity')
                ->setParameter('activity', $query->getActivity());
        } elseif ($query->getProject() !== null) {
            $qb->andWhere('a.project = :project')
                ->setParameter('project', $query->getProject());
        } elseif ($query->getCustomer() !== null) {
            $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $query->getCustomer());
        }

        return $this->getPager($qb->getQuery(), $query->getPage(), $query->getPageSize());
    }

    /**
     * @param Query $query
     * @param int $page
     * @param int $maxPerPage
     * @return Pagerfanta
     */
    protected function getPager(Query $query, $page = 1, $maxPerPage = 25)
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
