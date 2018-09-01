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
use App\Model\ProjectStatistic;
use App\Repository\Query\ProjectQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

/**
 * Class ProjectRepository
 */
class ProjectRepository extends AbstractRepository
{
    /**
     * @param $id
     * @return null|Project
     */
    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * @return int
     */
    public function countProject()
    {
        return $this->count([]);
    }

    /**
     * Retrieves statistics for one activity.
     *
     * @param Project $project
     * @return ProjectStatistic
     */
    public function getProjectStatistics(Project $project)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(t.id) as recordAmount')
            ->addSelect('SUM(t.duration) as recordDuration')
            ->addSelect('COUNT(DISTINCT(a.id)) as activityAmount')
            ->from(Activity::class, 'a')
            ->join(Timesheet::class, 't')
            ->where('a.project = :project')
            ->andWhere('t.activity = a.id')
        ;

        $result = $qb->getQuery()->execute(['project' => $project], Query::HYDRATE_ARRAY);

        $stats = new ProjectStatistic();

        if (isset($result[0])) {
            $dbStats = $result[0];

            $stats->setCount(1);
            $stats->setRecordAmount($dbStats['recordAmount']);
            $stats->setRecordDuration($dbStats['recordDuration']);
            $stats->setActivityAmount($dbStats['activityAmount']);
        }

        return $stats;
    }

    /**
     * Returns a query builder that is used for ProjectType and your own 'query_builder' option.
     *
     * @param Project|null $entity
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function builderForEntityType(Project $entity = null)
    {
        $query = new ProjectQuery();
        $query->setHiddenEntity($entity);
        $query->setResultType(ProjectQuery::RESULT_TYPE_QUERYBUILDER);

        return $this->findByQuery($query);
    }

    /**
     * @param ProjectQuery $query
     * @return QueryBuilder|Pagerfanta|array
     */
    public function findByQuery(ProjectQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // if we join activities, the maxperpage limit will limit the list
        // due to the raised amount of rows by projects * activities
        $qb->select('p', 'c')
            ->from(Project::class, 'p')
            ->join('p.customer', 'c')
            ->orderBy('p.' . $query->getOrderBy(), $query->getOrder());

        if (ProjectQuery::SHOW_VISIBLE == $query->getVisibility()) {
            if (!$query->isExclusiveVisibility()) {
                $qb->andWhere('c.visible = 1');
            }
            $qb->andWhere('p.visible = 1');

            /** @var Project $entity */
            $entity = $query->getHiddenEntity();
            if (null !== $entity) {
                $qb->orWhere('p.id = :project')->setParameter('project', $entity);
            }

            // TODO check for visibility of customer
        } elseif (ProjectQuery::SHOW_HIDDEN == $query->getVisibility()) {
            $qb->andWhere('p.visible = 0');
            // TODO check for visibility of customer
        }

        if (null !== $query->getCustomer()) {
            $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $query->getCustomer());
        }

        return $this->getBaseQueryResult($qb, $query);
    }
}
