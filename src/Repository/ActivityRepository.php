<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\ActivityStatistic;
use App\Repository\Query\ActivityQuery;
use Doctrine\ORM\Query;

/**
 * Class ActivityRepository
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
            ->distinct()
            ->from(Timesheet::class, 't')
            ->join('t.activity', 'a')
            ->join('a.project', 'p')
            ->join('p.customer', 'c')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere('a.visible = 1')
            ->andWhere('p.visible = 1')
            ->andWhere('c.visible = 1')
            ->groupBy('a.id', 't.id')
            ->orderBy('t.end', 'DESC')
            ->setMaxResults(10)
        ;

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        if (null !== $startFrom) {
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
     * @return int
     */
    public function countActivity()
    {
        return $this->count([]);
    }

    /**
     * Retrieves statistics for one activity.
     *
     * @param Activity $activity
     * @return ActivityStatistic
     */
    public function getActivityStatistics(Activity $activity)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(t.id) as totalRecords', 'SUM(t.duration) as totalDuration')
            ->from(Timesheet::class, 't')
            ->where('t.activity = :activity')
        ;

        $result = $qb->getQuery()->execute(['activity' => $activity], Query::HYDRATE_ARRAY);

        $stats = new ActivityStatistic();

        if (isset($result[0])) {
            $dbStats = $result[0];

            $stats->setCount(1);
            $stats->setRecordAmount($dbStats['totalRecords']);
            $stats->setRecordDuration($dbStats['totalDuration']);
        }

        return $stats;
    }

    /**
     * Returns a query builder that is used for ActivityType and your own 'query_builder' option.
     *
     * @param Activity|null $entity
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function builderForEntityType(Activity $entity = null, Project $project = null)
    {
        $query = new ActivityQuery();
        $query->setHiddenEntity($entity);
        $query->setResultType(ActivityQuery::RESULT_TYPE_QUERYBUILDER);
        $query->setProject($project);

        return $this->findByQuery($query);
    }

    /**
     * @param ActivityQuery $query
     * @return \Doctrine\ORM\QueryBuilder|\Pagerfanta\Pagerfanta
     */
    public function findByQuery(ActivityQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a', 'p', 'c')
            ->from(Activity::class, 'a')
            ->leftJoin('a.project', 'p')
            ->leftJoin('p.customer', 'c')
            ->orderBy('a.' . $query->getOrderBy(), $query->getOrder());

        if (ActivityQuery::SHOW_VISIBLE == $query->getVisibility()) {
            if (!$query->isExclusiveVisibility()) {
                $qb->andWhere('c.visible = 1');
                $qb->andWhere('p.visible = 1');
            }
            $qb->andWhere('a.visible = 1');

            /** @var Activity $entity */
            $entity = $query->getHiddenEntity();
            if (null !== $entity) {
                $qb->orWhere('a.id = :activity')->setParameter('activity', $entity);
            }
        } elseif (ActivityQuery::SHOW_HIDDEN == $query->getVisibility()) {
            $qb->andWhere('a.visible = 0');
        }

        if (null !== $query->getProject()) {
            $qb->andWhere('a.project = :project')
                ->setParameter('project', $query->getProject());
        } elseif (null !== $query->getCustomer()) {
            $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $query->getCustomer());
        }

        return $this->getBaseQueryResult($qb, $query);
    }
}
