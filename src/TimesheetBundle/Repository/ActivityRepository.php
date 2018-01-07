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
use AppBundle\Repository\AbstractRepository;
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Entity\Timesheet;
use TimesheetBundle\Model\ActivityStatistic;
use TimesheetBundle\Repository\Query\ActivityQuery;

/**
 * Class ActivityRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityRepository extends AbstractRepository
{

    /**
     * @param $id
     * @return null|Activity
     */
    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * @param User|null $user
     * @param \DateTime|null $startFrom
     * @return mixed
     */
    public function getRecentActivities(User $user = null, \DateTime $startFrom = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t', 'a', 'p', 'c')
            ->from('TimesheetBundle:Timesheet', 't')
            ->join('t.activity', 'a')
            ->join('a.project', 'p')
            ->join('p.customer', 'c')
            ->where($qb->expr()->isNotNull('t.end'))
            ->groupBy('a.id')
            ->orderBy('t.end', 'DESC')
            ->setMaxResults(10)
        ;

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        if ($startFrom !== null) {
            $qb->andWhere($qb->expr()->gt('t.begin', ':begin'))
                ->setParameter('begin', $startFrom);
        }

        $results = $qb->getQuery()->getResult();

        $activities = [];
        /* @var Timesheet $entry */
        foreach ($results as $entry) {
            $activities[] = $entry->getActivity();
        }

        return $activities;
    }

    /**
     * Return statistic data for all user.
     *
     * @return ActivityStatistic
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getGlobalStatistics()
    {
        $countAll = $this->getEntityManager()
            ->createQuery('SELECT COUNT(a.id) FROM TimesheetBundle:Activity a')
            ->getSingleScalarResult();

        $stats = new ActivityStatistic();
        $stats->setTotalAmount($countAll);
        return $stats;
    }

    /**
     * @param ActivityQuery $query
     * @return \Doctrine\ORM\QueryBuilder|\Pagerfanta\Pagerfanta
     */
    public function findByQuery(ActivityQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a', 'p', 'c')
            ->from('TimesheetBundle:Activity', 'a')
            ->join('a.project', 'p')
            ->join('p.customer', 'c')
            ->orderBy('a.' . $query->getOrderBy(), $query->getOrder());

        if ($query->getVisibility() == ActivityQuery::SHOW_VISIBLE) {
            $qb->andWhere('a.visible = 1');
            // TODO check for visibility of customer and project
        } elseif ($query->getVisibility() == ActivityQuery::SHOW_HIDDEN) {
            $qb->andWhere('a.visible = 0');
            // TODO check for visibility of customer and project
        }

        if ($query->getProject() !== null) {
            $qb->andWhere('a.project = :project')
                ->setParameter('project', $query->getProject());
        } elseif ($query->getCustomer() !== null) {
            $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $query->getCustomer());
        }

        return $this->getBaseQueryResult($qb, $query);
    }
}
