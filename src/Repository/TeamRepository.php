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
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\Loader\TeamLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\TeamQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends \Doctrine\ORM\EntityRepository<Team>
 */
class TeamRepository extends EntityRepository
{
    /**
     * @return Team[]
     */
    public function findAll(): array
    {
        $result = parent::findAll();

        $loader = new TeamLoader($this->getEntityManager());
        $loader->loadResults($result);

        return $result;
    }

    /**
     * @param int[] $teamIds
     * @return Team[]
     */
    public function findByIds(array $teamIds): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb
            ->where($qb->expr()->in('t.id', ':id'))
            ->setParameter('id', $teamIds)
        ;

        $teams = $qb->getQuery()->getResult();

        $loader = new TeamLoader($qb->getEntityManager());
        $loader->loadResults($teams);

        return $teams;
    }

    /**
     * @param Team $team
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveTeam(Team $team)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($team);
        $entityManager->flush();
    }

    /**
     * @param TeamMember $member
     * @throws ORMException
     */
    public function removeTeamMember(TeamMember $member)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($member);
    }

    /**
     * @param Team $team
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteTeam(Team $team)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($team);
        $entityManager->flush();
    }

    /**
     * Returns a query builder that is used for TeamType and your own 'query_builder' option.
     *
     * @param TeamQuery $query
     * @return QueryBuilder
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
        $paginator = new Pagination($this->getPaginatorForQuery($query));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }

    protected function getPaginatorForQuery(TeamQuery $query): PaginatorInterface
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->countDistinct('t.id'))
        ;
        $counter = (int) $qb->getQuery()->getSingleScalarResult();

        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new TeamLoader($qb->getEntityManager()), $qb, $counter);
    }

    /**
     * @param TeamQuery $query
     * @return Timesheet[]
     */
    public function getTeamsForQuery(TeamQuery $query): iterable
    {
        // this is using the paginator internally, as it will load all joined entities into the working unit
        // do not "optimize" to use the query directly, as it would results in hundreds of additional lazy queries
        $paginator = $this->getPaginatorForQuery($query);

        return $paginator->getAll();
    }

    private function getQueryBuilderForQuery(TeamQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('t')
            ->from(Team::class, 't')
        ;

        $orderBy = $query->getOrderBy();
        switch ($orderBy) {
            default:
                $orderBy = 't.' . $orderBy;
                break;
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
     * @param QueryBuilder $qb
     * @param User|null $user
     * @param Team[] $teams
     */
    private function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = [])
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

        // this query should limit to teams where the user is a teamlead (eg. in dropdowns or listing page)
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
}
