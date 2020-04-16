<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\Loader\TeamLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\TeamQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class TeamRepository extends EntityRepository
{
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        /** @var Team|null $team */
        $team = parent::find($id, $lockMode, $lockVersion);
        if (null === $team) {
            return null;
        }

        $loader = new TeamLoader($this->getEntityManager());
        $loader->loadResults([$team]);

        return $team;
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

    public function getPagerfantaForQuery(TeamQuery $query): Pagerfanta
    {
        $paginator = new Pagerfanta($this->getPaginatorForQuery($query));
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
            case 'teamlead':
                $qb->leftJoin('t.teamlead', 'lead');
                $orderBy = 'lead.username';
                break;
            default:
                $orderBy = 't.' . $orderBy;
                break;
        }

        if ($query->hasUsers()) {
            $qb->orWhere(
                $qb->expr()->in('t.teamlead', ':user'),
                $qb->expr()->isMemberOf(':user', 't.users')
            )
            ->setParameter('user', $query->getUsers());
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
        if (null !== $user && ($user->isSuperAdmin() || $user->isAdmin())) {
            return;
        }

        $or = $qb->expr()->orX();

        if (null !== $user) {
            $or->add($qb->expr()->eq('t.teamlead', ':id'));
            $qb->setParameter('id', $user);
        }

        if (!empty($teams)) {
            $ids = [];
            foreach ($teams as $team) {
                $ids[] = $team->getId();
            }
            $or->add($qb->expr()->in('t.id', $ids));
        }

        $qb->andWhere($or);
    }
}
