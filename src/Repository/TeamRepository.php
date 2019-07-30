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

        $qb->orderBy('t.' . $query->getOrderBy(), $query->getOrder());

        return $qb;
    }
}
