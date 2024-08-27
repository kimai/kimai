<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Repository\Loader\TeamLoader;
use App\Repository\Paginator\LoaderQueryPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\TeamQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends EntityRepository<Team>
 */
class TeamRepository extends EntityRepository
{
    /**
     * @param int[] $teamIds
     * @return Team[]
     */
    public function findByIds(array $teamIds): array
    {
        $ids = array_filter(
            array_unique($teamIds),
            function ($value) {
                return $value > 0;
            }
        );

        if (\count($ids) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('t');
        $qb
            ->where($qb->expr()->in('t.id', ':id'))
            ->setParameter('id', $ids)
        ;

        return $this->getTeams($this->prepareTeamQuery($qb->getQuery()));
    }

    public function saveTeam(Team $team): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($team);
        $entityManager->flush();
    }

    public function removeTeamMember(TeamMember $member): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($member);
    }

    public function deleteTeam(Team $team): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($team);
        $entityManager->flush();
    }

    /**
     * Returns a query builder that is used for TeamType and your own 'query_builder' option.
     */
    public function getQueryBuilderForFormType(TeamQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t')
            ->from(Team::class, 't')
            ->orderBy('t.name', 'ASC');

        $this->addPermissionCriteria($qb, $query->getCurrentUser(), $query->getTeams());

        return $qb;
    }

    public function getPagerfantaForQuery(TeamQuery $query): Pagination
    {
        return new Pagination($this->getPaginatorForQuery($query), $query);
    }

    /**
     * @return PaginatorInterface<Team>
     */
    private function getPaginatorForQuery(TeamQuery $teamQuery): PaginatorInterface
    {
        $qb = $this->getQueryBuilderForQuery($teamQuery);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->countDistinct('t.id'))
        ;
        /** @var int<0, max> $counter */
        $counter = (int) $qb->getQuery()->getSingleScalarResult();

        $query = $this->createTeamQuery($teamQuery);

        return new LoaderQueryPaginator(new TeamLoader($qb->getEntityManager()), $query, $counter);
    }

    /**
     * @return Team[]
     */
    public function getTeamsForQuery(TeamQuery $query): iterable
    {
        return $this->getTeams($this->createTeamQuery($query));
    }

    /**
     * @param Query<Team> $query
     * @return Team[]
     */
    public function getTeams(Query $query): array
    {
        /** @var array<Team> $teams */
        $teams = $query->execute();

        $loader = new TeamLoader($this->getEntityManager());
        $loader->loadResults($teams);

        return $teams;
    }

    private function getQueryBuilderForQuery(TeamQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('t');

        $orderBy = $query->getOrderBy();
        switch ($orderBy) {
            default:
                $orderBy = 't.' . $orderBy;
                break;
        }

        if ($query->hasCustomers()) {
            $qb->leftJoin('t.customers', 'qCustomers');
            $qb->orWhere(
                $qb->expr()->in('qCustomers', ':customers')
            );
            $qb->setParameter('customers', $query->getCustomers());
        }

        if ($query->hasProjects()) {
            $qb->leftJoin('t.projects', 'qProjects');
            $qb->orWhere(
                $qb->expr()->in('qProjects', ':projects')
            );
            $qb->setParameter('projects', $query->getProjects());
        }

        if ($query->hasActivities()) {
            $qb->leftJoin('t.activities', 'qActivities');
            $qb->orWhere(
                $qb->expr()->in('qActivities', ':activities')
            );
            $qb->setParameter('activities', $query->getActivities());
        }

        if ($query->hasUsers()) {
            $qb->leftJoin('t.members', 'qMembers');
            $qb->orWhere(
                $qb->expr()->in('qMembers.user', ':user')
            );
            $qb->setParameter('user', $query->getUsers());
        }

        $qb->addOrderBy($orderBy, $query->getOrder());

        if (!empty($query->getSearchTerm())) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.name', ':likeContains')
                )
            );
            $qb->setParameter('likeContains', '%' . $query->getSearchTerm() . '%');
        }

        $this->addPermissionCriteria($qb, $query->getCurrentUser(), $query->getTeams());

        return $qb;
    }

    /**
     * @param Team[] $teams
     */
    private function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = []): void
    {
        // make sure that all queries without a user see all user
        if (null === $user && empty($teams)) {
            return;
        }

        // make sure that admins see all user
        if (null !== $user && $user->canSeeAllData()) {
            return;
        }

        // this is an OR on purpose because we either query only for teams where the user is teamlead
        // OR we query for all teams where the user is a member - in later case $teams is not empty
        $or = $qb->expr()->orX();

        // this query should limit to teams where the user is a teamlead (e.g. in dropdowns or listing page)
        if (null !== $user) {
            $qb->leftJoin('t.members', 'members');
            $or->add(
                $qb->expr()->andX(
                    $qb->expr()->eq('members.user', ':id'),
                    $qb->expr()->eq('members.teamlead', true)
                )
            );
            $qb->setParameter('id', $user);
        }

        // this is primarily used, if we want to query for teams of the current user
        // and not 'teamlead_only' as used in the teams form type
        if (!empty($teams)) {
            $ids = [];
            foreach ($teams as $team) {
                $ids[] = $team->getId();
            }
            $or->add($qb->expr()->in('t.id', ':teamIds'));
            $qb->setParameter('teamIds', array_unique($ids));
        }

        if ($or->count() > 0) {
            $qb->andWhere($or);
        }
    }

    /**
     * @return Query<Team>
     */
    private function createTeamQuery(TeamQuery $teamQuery): Query
    {
        $query = $this->getQueryBuilderForQuery($teamQuery)->getQuery();
        $query = $this->prepareTeamQuery($query);

        return $query;
    }

    /**
     * @param Query<Team> $query
     * @return Query<Team>
     */
    public function prepareTeamQuery(Query $query): Query
    {
        $this->getEntityManager()->getConfiguration()->setEagerFetchBatchSize(300);

        // $query->setFetchMode(Team::class, 'members', ClassMetadata::FETCH_EAGER);
        // $query->setFetchMode(Team::class, 'customers', ClassMetadata::FETCH_EAGER);
        // $query->setFetchMode(Team::class, 'projects', ClassMetadata::FETCH_EAGER);
        // $query->setFetchMode(Team::class, 'activities', ClassMetadata::FETCH_EAGER);

        return $query;
    }
}
