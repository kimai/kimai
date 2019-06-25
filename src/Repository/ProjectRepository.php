<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Model\ProjectStatistic;
use App\Repository\Query\ProjectQuery;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

/**
 * Class ProjectRepository
 */
class ProjectRepository extends AbstractRepository
{
    /**
     * @param Project $project
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveProject(Project $project)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($project);
        $entityManager->flush();
    }

    /**
     * @param int $id
     * @return null|Project
     */
    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * @param null|bool $visible
     * @return int
     */
    public function countProject($visible = null)
    {
        if (null !== $visible) {
            return $this->count(['visible' => (bool) $visible]);
        }

        return $this->count([]);
    }

    public function getProjectStatistics(Project $project): ProjectStatistic
    {
        $stats = new ProjectStatistic();

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->addSelect('COUNT(t.id) as recordAmount')
            ->addSelect('SUM(t.duration) as recordDuration')
            ->addSelect('SUM(t.rate) as recordRate')
            ->from(Timesheet::class, 't')
            ->andWhere('t.project = :project')
        ;
        $timesheetResult = $qb->getQuery()->execute(['project' => $project], Query::HYDRATE_ARRAY);

        if (isset($timesheetResult[0])) {
            $stats->setRecordAmount($timesheetResult[0]['recordAmount']);
            $stats->setRecordDuration($timesheetResult[0]['recordDuration']);
            $stats->setRecordRate($timesheetResult[0]['recordRate']);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(a.id) as activityAmount')
            ->from(Activity::class, 'a')
            ->andWhere('a.project = :project')
        ;
        $resultActivities = $qb->getQuery()->execute(['project' => $project], Query::HYDRATE_ARRAY);

        if (isset($resultActivities[0])) {
            $resultActivities = $resultActivities[0];

            $stats->setActivityAmount($resultActivities['activityAmount']);
        }

        return $stats;
    }

    /**
     * Returns a query builder that is used for ProjectType and your own 'query_builder' option.
     *
     * @param Project|int|null $entity
     * @param Customer|int|null $customer
     * @return array|QueryBuilder|Pagerfanta
     */
    public function builderForEntityType($entity = null, $customer = null)
    {
        $query = new ProjectQuery();
        $query->setHiddenEntity($entity);
        $query->setCustomer($customer);
        $query->setResultType(ProjectQuery::RESULT_TYPE_QUERYBUILDER);
        $query->setOrderBy('name');

        return $this->findByQuery($query);
    }

    /**
     * @param ProjectQuery $query
     * @return QueryBuilder|Pagerfanta|array
     */
    public function findByQuery(ProjectQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // if we join activities, the max-per-page limit will limit the list
        // due to the raised amount of rows by projects * activities
        $qb->select('p', 'c')
            ->from(Project::class, 'p')
            ->join('p.customer', 'c')
            ->orderBy('p.' . $query->getOrderBy(), $query->getOrder());

        if (ProjectQuery::SHOW_VISIBLE == $query->getVisibility()) {
            if (!$query->isExclusiveVisibility()) {
                $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
            }
            $qb->andWhere($qb->expr()->eq('p.visible', ':visible'));
            $qb->setParameter('visible', true, \PDO::PARAM_BOOL);

            $entity = $query->getHiddenEntity();
            if (null !== $entity) {
                $qb->orWhere('p.id = :project')->setParameter('project', $entity);
            }

            // TODO check for visibility of customer
        } elseif (ProjectQuery::SHOW_HIDDEN == $query->getVisibility()) {
            $qb->andWhere($qb->expr()->eq('p.visible', ':visible'));
            $qb->setParameter('visible', false, \PDO::PARAM_BOOL);
            // TODO check for visibility of customer
        }

        if (null !== $query->getCustomer()) {
            $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $query->getCustomer());
        }

        if (!empty($query->getIgnoredEntities())) {
            $qb->andWhere('p.id NOT IN(:ignored)');
            $qb->setParameter('ignored', $query->getIgnoredEntities());
        }

        return $this->getBaseQueryResult($qb, $query);
    }

    /**
     * @param Project $delete
     * @param Project|null $replace
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteProject(Project $delete, ?Project $replace = null)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if (null !== $replace) {
                $qb = $em->createQueryBuilder();
                $qb
                    ->update(Timesheet::class, 't')
                    ->set('t.project', ':replace')
                    ->where('t.project = :delete')
                    ->setParameter('delete', $delete)
                    ->setParameter('replace', $replace)
                    ->getQuery()
                    ->execute();

                $qb = $em->createQueryBuilder();
                $qb
                    ->update(Activity::class, 'a')
                    ->set('a.project', ':replace')
                    ->where('a.project = :delete')
                    ->setParameter('delete', $delete)
                    ->setParameter('replace', $replace)
                    ->getQuery()
                    ->execute();
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
