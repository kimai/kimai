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

    protected function queryThisMonth($select, User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // FIXME
        $end = new DateTime();
        $begin = $end->sub(new \DateInterval("P30D"));

        $qb->select($select)
            ->from('TimesheetBundle:Timesheet', 't')
            ->where($qb->expr()->gt('t.begin', ':from'))
            ->andWhere($qb->expr()->gt('t.end', ':to'))
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

        $stats = new TimesheetStatistic();
        $stats->setAmountTotal($rateTotal);
        $stats->setDurationTotal($durationTotal);
        $stats->setAmountThisMonth($amountMonth);
        $stats->setDurationThisMonth($durationMonth);

        return $stats;
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
            ->where('t.begin > 0')
            ->andWhere('t.end is NULL');

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
