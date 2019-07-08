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
use App\Repository\Loader\ProjectLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\ProjectFormTypeQuery;
use App\Repository\Query\ProjectQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

/**
 * Class ProjectRepository
 */
class ProjectRepository extends EntityRepository
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
     * @param ProjectFormTypeQuery $query
     * @return QueryBuilder
     */
    public function getQueryBuilderForFormType(ProjectFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('p', 'c')
            ->from(Project::class, 'p')
            ->leftJoin('p.customer', 'c')
            ->addOrderBy('c.name', 'ASC')
            ->addOrderBy('p.name', 'ASC')
        ;

        $qb->andWhere($qb->expr()->eq('p.visible', ':visible'));
        $qb->andWhere($qb->expr()->eq('c.visible', ':customer_visible'));
        $qb->setParameter('visible', true, \PDO::PARAM_BOOL);
        $qb->setParameter('customer_visible', true, \PDO::PARAM_BOOL);

        if (null !== $query->getProject()) {
            $qb->orWhere('p.id = :project')->setParameter('project', $query->getProject());
        }

        if (null !== $query->getCustomer()) {
            $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $query->getCustomer());
        }

        if (null !== $query->getProjectToIgnore()) {
            $qb->andWhere($qb->expr()->neq('p.id', ':ignored'));
            $qb->setParameter('ignored', $query->getProjectToIgnore());
        }

        return $qb;
    }

    private function getQueryBuilderForQuery(ProjectQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('p')
            ->from(Project::class, 'p')
        ;

        if (in_array($query->getVisibility(), [ProjectQuery::SHOW_VISIBLE, ProjectQuery::SHOW_HIDDEN])) {
            $qb
                ->leftJoin('p.customer', 'c')
                ->andWhere($qb->expr()->eq('p.visible', ':visible'))
                ->andWhere($qb->expr()->eq('c.visible', ':customer_visible'))
            ;

            if (ProjectQuery::SHOW_VISIBLE === $query->getVisibility()) {
                $qb->setParameter('visible', true, \PDO::PARAM_BOOL);
            } elseif (ProjectQuery::SHOW_HIDDEN === $query->getVisibility()) {
                $qb->setParameter('visible', false, \PDO::PARAM_BOOL);
            }

            $qb->setParameter('customer_visible', true, \PDO::PARAM_BOOL);
        }

        if (null !== $query->getCustomer()) {
            $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $query->getCustomer());
        }

        $qb->orderBy('p.' . $query->getOrderBy(), $query->getOrder());

        return $qb;
    }

    public function getPagerfantaForQuery(ProjectQuery $query): Pagerfanta
    {
        $paginator = new Pagerfanta($this->getPaginatorForQuery($query));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }

    private function getPaginatorForQuery(ProjectQuery $query): PaginatorInterface
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->countDistinct('p.id'))
        ;
        $counter = (int) $qb->getQuery()->getSingleScalarResult();

        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new ProjectLoader($qb->getEntityManager()), $qb, $counter);
    }

    /**
     * @param ProjectQuery $query
     * @return Project[]
     */
    public function getProjectsForQuery(ProjectQuery $query): iterable
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $results = $qb->getQuery()->execute();
        $loader = new ProjectLoader($qb->getEntityManager());
        $loader->loadResults($results);

        return $results;
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
