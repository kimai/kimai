<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\Loader\ActivityLoader;
use App\Repository\Paginator\LoaderQueryPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\ActivityFormTypeQuery;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\ActivityQueryHydrate;
use App\Utils\Pagination;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends \Doctrine\ORM\EntityRepository<Activity>
 */
class ActivityRepository extends EntityRepository
{
    use RepositorySearchTrait;

    /**
     * @param int[] $activityIds
     * @return array<Activity>
     */
    public function findByIds(array $activityIds): array
    {
        $ids = array_filter(
            array_unique($activityIds),
            function ($value) {
                return $value > 0;
            }
        );

        if (\count($ids) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('a');
        $qb
            ->where($qb->expr()->in('a.id', ':id'))
            ->setParameter('id', $ids)
        ;

        return $this->getActivities($this->prepareActivityQuery($qb->getQuery()));
    }

    public function saveActivity(Activity $activity): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($activity);
        $entityManager->flush();
    }

    public function countActivity(?bool $visible = null): int
    {
        if (null !== $visible) {
            return $this->count(['visible' => $visible]);
        }

        return $this->count([]);
    }

    /**
     * @param array<Team> $teams
     */
    private function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = [], bool $globalsOnly = false): void
    {
        $permissions = $this->getPermissionCriteria($qb, $user, $teams, $globalsOnly);
        if ($permissions->count() > 0) {
            $qb->andWhere($permissions);
        }
    }

    /**
     * @param array<Team> $teams
     */
    private function getPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = [], bool $globalsOnly = false): Andx
    {
        $andX = $qb->expr()->andX();

        // make sure that all queries without a user see all projects
        if (null === $user && empty($teams)) {
            return $andX;
        }

        // make sure that admins see all activities
        if (null !== $user && $user->canSeeAllData()) {
            return $andX;
        }

        if (null !== $user) {
            $teams = array_merge($teams, $user->getTeams());
        }

        if (empty($teams)) {
            $andX->add('SIZE(a.teams) = 0');
            if (!$globalsOnly) {
                $andX->add('SIZE(p.teams) = 0');
                $andX->add('SIZE(c.teams) = 0');
            }

            return $andX;
        }

        $orActivity = $qb->expr()->orX(
            'SIZE(a.teams) = 0',
            $qb->expr()->isMemberOf(':teams', 'a.teams')
        );
        $andX->add($orActivity);

        if (!$globalsOnly) {
            $orProject = $qb->expr()->orX(
                'SIZE(p.teams) = 0',
                $qb->expr()->isMemberOf(':teams', 'p.teams')
            );
            $andX->add($orProject);

            $orCustomer = $qb->expr()->orX(
                'SIZE(c.teams) = 0',
                $qb->expr()->isMemberOf(':teams', 'c.teams')
            );
            $andX->add($orCustomer);
        }

        $ids = array_values(array_unique(array_map(function (Team $team) {
            return $team->getId();
        }, $teams)));

        $qb->setParameter('teams', $ids);

        return $andX;
    }

    /**
     * Returns a query builder that is used for ActivityType and your own 'query_builder' option.
     *
     * @internal
     */
    public function getQueryBuilderForFormType(ActivityFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a')
            ->from(Activity::class, 'a')
            ->addOrderBy('a.project', 'DESC')
            ->addOrderBy('a.name', 'ASC')
        ;

        $mainQuery = $qb->expr()->andX();

        $mainQuery->add($qb->expr()->eq('a.visible', ':visible'));
        $qb->setParameter('visible', true, ParameterType::BOOLEAN);

        if (!$query->isGlobalsOnly()) {
            $qb
                ->addSelect('p')
                ->addSelect('c')
                ->leftJoin('a.project', 'p')
                ->leftJoin('p.customer', 'c');

            $mainQuery->add(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.project'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('p.visible', ':is_visible'),
                        $qb->expr()->eq('c.visible', ':is_visible')
                    )
                )
            );

            $qb->setParameter('is_visible', true, ParameterType::BOOLEAN);
        }

        if ($query->isGlobalsOnly()) {
            $mainQuery->add($qb->expr()->isNull('a.project'));
        } elseif ($query->hasProjects()) {
            $orX = $qb->expr()->orX(
                $qb->expr()->in('a.project', ':project')
            );

            $includeGlobals = true;
            // projects have a setting to disallow global activities, and we check for it only
            // if we query for exactly one project (usually used in dropdown queries)
            if (\count($query->getProjects()) === 1) {
                $project = $query->getProjects()[0];
                if (!$project instanceof Project) {
                    $project = $this->getEntityManager()->getRepository(Project::class)->find($project);
                }
                if ($project instanceof Project) {
                    $includeGlobals = $project->isGlobalActivities();
                }
            }

            if ($includeGlobals) {
                $orX->add($qb->expr()->isNull('a.project'));
            }

            $mainQuery->add($orX);
            $qb->setParameter('project', $query->getProjects());
        }

        $permissions = $this->getPermissionCriteria($qb, $query->getUser(), $query->getTeams(), $query->isGlobalsOnly());
        if ($permissions->count() > 0) {
            $mainQuery->add($permissions);
        }

        $outerQuery = $qb->expr()->orX();

        if ($query->hasActivities()) {
            $outerQuery->add($qb->expr()->in('a.id', ':activity'));
            $qb->setParameter('activity', $query->getActivities());
        }

        if (null !== $query->getActivityToIgnore()) {
            $mainQuery = $qb->expr()->andX(
                $mainQuery,
                $qb->expr()->neq('a.id', ':ignored')
            );
            $qb->setParameter('ignored', $query->getActivityToIgnore());
        }

        $outerQuery->add($mainQuery);

        $qb->andWhere($outerQuery);

        return $qb;
    }

    private function getQueryBuilderForQuery(ActivityQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        $qb
            ->leftJoin('a.project', 'p')
            ->leftJoin('p.customer', 'c')
        ;

        if (\count($query->getActivityIds()) > 0) {
            $qb->andWhere($qb->expr()->in('a.id', ':id'))->setParameter('id', $query->getActivityIds());
        }

        foreach ($query->getOrderGroups() as $orderBy => $order) {
            switch ($orderBy) {
                case 'project':
                    $orderBy = 'p.name';
                    break;
                case 'customer':
                    $orderBy = 'c.name';
                    break;
                default:
                    $orderBy = 'a.' . $orderBy;
                    break;
            }
            $qb->addOrderBy($orderBy, $order);
        }

        $where = $qb->expr()->andX();

        if (!$query->isShowBoth()) {
            $where->add($qb->expr()->eq('a.visible', ':visible'));

            if (!$query->isGlobalsOnly()) {
                $where->add(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('a.project'),
                        $qb->expr()->andX(
                            $qb->expr()->eq('p.visible', ':is_visible'),
                            $qb->expr()->eq('c.visible', ':is_visible')
                        )
                    )
                );
                $qb->setParameter('is_visible', true, ParameterType::BOOLEAN);
            }

            if ($query->isShowVisible()) {
                $qb->setParameter('visible', true, ParameterType::BOOLEAN);
            } elseif ($query->isShowHidden()) {
                $qb->setParameter('visible', false, ParameterType::BOOLEAN);
            }
        }

        if ($query->isGlobalsOnly()) {
            $where->add($qb->expr()->isNull('a.project'));
        } elseif ($query->hasProjects()) {
            $orX = $qb->expr()->orX(
                $qb->expr()->in('a.project', ':project')
            );

            if (!$query->isExcludeGlobals()) {
                $includeGlobals = true;
                // projects have a setting to disallow global activities, and we check for it only
                // if we query for exactly one project (usually used in dropdown queries)
                if (\count($query->getProjects()) === 1) {
                    $includeGlobals = $query->getProjects()[0]->isGlobalActivities();
                }
                if ($includeGlobals) {
                    $orX->add($qb->expr()->isNull('a.project'));
                }
            }

            $where->add($orX);
            $qb->setParameter('project', $query->getProjectIds());
        } elseif ($query->hasCustomers()) {
            $where->add($qb->expr()->in('p.customer', ':customer'));
            $qb->setParameter('customer', $query->getCustomerIds());
        }

        if ($where->count() > 0) {
            $qb->andWhere($where);
        }

        $this->addPermissionCriteria($qb, $query->getCurrentUser(), $query->getTeams(), $query->isGlobalsOnly());

        $this->addSearchTerm($qb, $query);

        return $qb;
    }

    private function getMetaFieldClass(): string
    {
        return ActivityMeta::class;
    }

    private function getMetaFieldName(): string
    {
        return 'activity';
    }

    /**
     * @return array<string>
     */
    private function getSearchableFields(): array
    {
        return ['a.name', 'a.comment', 'a.number'];
    }

    /**
     * @return int<0, max>
     */
    public function countActivitiesForQuery(ActivityQuery $query): int
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select($qb->expr()->countDistinct('a.id'))
        ;

        return (int) $qb->getQuery()->getSingleScalarResult(); // @phpstan-ignore-line
    }

    public function getPagerfantaForQuery(ActivityQuery $query): Pagination
    {
        return new Pagination($this->getPaginatorForQuery($query), $query);
    }

    /**
     * @return PaginatorInterface<Activity>
     */
    private function getPaginatorForQuery(ActivityQuery $activityQuery): PaginatorInterface
    {
        $counter = $this->countActivitiesForQuery($activityQuery);
        $query = $this->createActivityQuery($activityQuery);

        return new LoaderQueryPaginator(new ActivityLoader($this->getEntityManager()), $query, $counter);
    }

    /**
     * @return Activity[]
     */
    public function getActivitiesForQuery(ActivityQuery $query): array
    {
        return $this->getActivities($this->createActivityQuery($query));
    }

    /**
     * @param Query<Activity> $query
     * @return Activity[]
     */
    public function getActivities(Query $query): array
    {
        /** @var array<Activity> $activities */
        $activities = $query->execute();

        $loader = new ActivityLoader($this->getEntityManager());
        $loader->loadResults($activities);

        return $activities;
    }

    public function deleteActivity(Activity $delete, ?Activity $replace = null): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if (null !== $replace) {
                $qb = $em->createQueryBuilder();
                $qb->update(Timesheet::class, 't')
                    ->set('t.activity', ':replace')
                    ->where('t.activity = :delete')
                    ->setParameter('delete', $delete)
                    ->setParameter('replace', $replace);

                $qb->getQuery()->execute();
            }

            $em->remove($delete);
            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    /**
     * @return Query<Activity>
     */
    private function createActivityQuery(ActivityQuery $activityQuery): Query
    {
        $query = $this->getQueryBuilderForQuery($activityQuery)->getQuery();
        $query = $this->prepareActivityQuery($query);

        foreach ($activityQuery->getHydrate() as $hydrate) {
            switch ($hydrate) {
                case ActivityQueryHydrate::TEAMS:
                    // does not yet work, see https://github.com/doctrine/orm/pull/8391
                    // $query->setFetchMode(Activity::class, 'teams', ClassMetadata::FETCH_EAGER);
                    break;

                case ActivityQueryHydrate::TEAM_MEMBER:
                    // does not yet work, see https://github.com/doctrine/orm/issues/11254
                    // $query->setFetchMode(Activity::class, 'teams', ClassMetadata::FETCH_EAGER);
                    // $query->setFetchMode(Team::class, 'members', ClassMetadata::FETCH_EAGER);
                    // $query->setFetchMode(TeamMember::class, 'user', ClassMetadata::FETCH_EAGER);
                    break;
            }
        }

        return $query;
    }

    /**
     * @param Query<Activity> $query
     * @return Query<Activity>
     */
    public function prepareActivityQuery(Query $query): Query
    {
        $this->getEntityManager()->getConfiguration()->setEagerFetchBatchSize(300);

        $query->setFetchMode(Activity::class, 'meta', ClassMetadata::FETCH_EAGER);
        $query->setFetchMode(Activity::class, 'project', ClassMetadata::FETCH_EAGER);

        // $query->setFetchMode(Project::class, 'customer', ClassMetadata::FETCH_EAGER);

        return $query;
    }
}
