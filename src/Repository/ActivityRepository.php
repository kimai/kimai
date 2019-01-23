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
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

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
     * @return Timesheet[]
     */
    public function getRecentActivities(User $user = null, \DateTime $startFrom = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t', 'a', 'p', 'c')
            ->distinct()
            ->from(Timesheet::class, 't')
            ->join('t.activity', 'a')
            ->join('t.project', 'p')
            ->join('p.customer', 'c')
            ->andWhere($qb->expr()->isNotNull('t.end'))
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

        return $qb->getQuery()->getResult();
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
     * @param Activity|string|null $activity
     * @param Project|string|null $project
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function builderForEntityType($activity = null, $project = null)
    {
        $query = new ActivityQuery();
        $query->setHiddenEntity($activity);
        $query->setResultType(ActivityQuery::RESULT_TYPE_QUERYBUILDER);
        $query->setProject($project);
        $query->setOrderGlobalsFirst(true);
        $query->setOrderBy('name');

        if (null === $activity && $project === null) {
            $query->setGlobalsOnly(true);
        }

        return $this->findByQuery($query);
    }

    /**
     * @param ActivityQuery $query
     * @return QueryBuilder|Pagerfanta|array
     */
    public function findByQuery(ActivityQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a', 'p', 'c')
            ->from(Activity::class, 'a')
            ->leftJoin('a.project', 'p')
            ->leftJoin('p.customer', 'c');

        if ($query->isOrderGlobalsFirst()) {
            $qb->orderBy('a.project', 'ASC');
        }

        $qb->addOrderBy('a.' . $query->getOrderBy(), $query->getOrder());

        $where = $qb->expr()->andX();

        if (ActivityQuery::SHOW_VISIBLE == $query->getVisibility()) {
            $where->add('a.visible = :visible');
            if (!$query->isExclusiveVisibility()) {
                $where->add(
                    $qb->expr()->orX(
                        $qb->expr()->eq('c.visible', ':visible'),
                        $qb->expr()->isNull('c.visible')
                    )
                );
                $where->add(
                    $qb->expr()->orX(
                        $qb->expr()->eq('p.visible', ':visible'),
                        $qb->expr()->isNull('p.visible')
                    )
                );
            }
            $qb->setParameter('visible', 1);
        } elseif (ActivityQuery::SHOW_HIDDEN == $query->getVisibility()) {
            $where->add('a.visible = :visible');
            $qb->setParameter('visible', 0);
        }

        if ($query->isGlobalsOnly()) {
            $where->add($qb->expr()->isNull('a.project'));
        } elseif (null !== $query->getProject()) {
            $where->add(
                $qb->expr()->orX(
                    $qb->expr()->eq('a.project', ':project'),
                    $qb->expr()->isNull('a.project')
                )
            );
            $qb->setParameter('project', $query->getProject());
        } elseif (null !== $query->getCustomer()) {
            $where->add('p.customer = :customer');
            $qb->setParameter('customer', $query->getCustomer());
        }

        if (!empty($query->getIgnoredEntities())) {
            $qb->andWhere('a.id NOT IN(:ignored)');
            $qb->setParameter('ignored', $query->getIgnoredEntities());
        }

        $or = $qb->expr()->orX();

        // this must always be the last part before the or
        $or->add($where);

        // this must always be the last part of the query
        /** @var Activity $entity */
        $entity = $query->getHiddenEntity();
        if (null !== $entity) {
            $or->add($qb->expr()->eq('a.id', ':activity'));
            $qb->setParameter('activity', $entity);
        }

        if ($or->count() > 0) {
            $qb->andWhere($or);
        }

        return $this->getBaseQueryResult($qb, $query);
    }

    /**
     * @param Activity $delete
     * @param Activity|null $replace
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteActivity(Activity $delete, ?Activity $replace = null)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if (null !== $replace) {
                $qb = $em->createQueryBuilder();
                $query = $qb
                    ->update(Timesheet::class, 't')
                    ->set('t.activity', ':replace')
                    ->where('t.activity = :delete')
                    ->setParameter('delete', $delete)
                    ->setParameter('replace', $replace)
                    ->getQuery();

                $result = $query->execute();
            }

            $em->remove($delete);
            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }
    }
}
