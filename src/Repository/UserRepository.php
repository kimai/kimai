<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\Loader\UserLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\UserFormTypeQuery;
use App\Repository\Query\UserQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends \Doctrine\ORM\EntityRepository<User>
 */
class UserRepository extends EntityRepository implements UserLoaderInterface
{
    public function getById($id): ?User
    {
        @trigger_error('UserRepository::getById is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return $this->getUserById($id);
    }

    /**
     * @param User $user
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveUser(User $user)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();
    }

    /**
     * Used to fetch a user by its ID.
     *
     * @param int $id
     * @return null|User
     */
    public function getUserById($id): ?User
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'p', 't', 'tu', 'tl')
            ->leftJoin('u.preferences', 'p')
            ->leftJoin('u.teams', 't')
            ->leftJoin('t.users', 'tu')
            ->leftJoin('t.teamlead', 'tl')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Overwritten to fetch preferences when using the Profile controller actions.
     * Depends on the query, some magic mechanisms like the ParamConverter will use this method to fetch the user.
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        if (\count($criteria) == 1 && isset($criteria['username'])) {
            return $this->loadUserByUsername($criteria['username']);
        }

        return parent::findOneBy($criteria, $orderBy);
    }

    /**
     * @param null|bool $enabled
     * @return int
     */
    public function countUser($enabled = null)
    {
        if (null !== $enabled) {
            return $this->count(['enabled' => (bool) $enabled]);
        }

        return $this->count([]);
    }

    /**
     * @param UserQuery $query
     * @return array|\Doctrine\ORM\QueryBuilder|\Pagerfanta\Pagerfanta
     * @deprecated since 1.4, use getUsersForQuery() instead
     */
    public function findByQuery(UserQuery $query)
    {
        @trigger_error('UserRepository::findByQuery() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);
        $qb = $this->getQueryBuilderForQuery($query);

        if (BaseQuery::RESULT_TYPE_PAGER === $query->getResultType()) {
            $paginator = new Pagerfanta(new DoctrineORMAdapter($qb->getQuery(), false));
            $paginator->setMaxPerPage($query->getPageSize());
            $paginator->setCurrentPage($query->getPage());

            return $paginator;
        }

        if (BaseQuery::RESULT_TYPE_OBJECTS === $query->getResultType()) {
            return $qb->getQuery()->execute();
        }

        return $qb;
    }

    /**
     * @param string $username
     * @return null|User
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'p', 't', 'tu', 'tl')
            ->leftJoin('u.preferences', 'p')
            ->leftJoin('u.teams', 't')
            ->leftJoin('t.users', 'tu')
            ->leftJoin('t.teamlead', 'tl')
            ->where('u.username = :username')
            ->orWhere('u.email = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getQueryBuilderForFormType(UserFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $or = $qb->expr()->orX();

        if ($query->isShowVisible()) {
            $or->add($qb->expr()->eq('u.enabled', ':enabled'));
            $qb->setParameter('enabled', true, \PDO::PARAM_BOOL);
        }

        $includeAlways = $query->getUsersAlwaysIncluded();
        if (!empty($includeAlways)) {
            $or->add($qb->expr()->in('u', ':users'));
            $qb->setParameter('users', $includeAlways);
        }

        if ($or->count() > 0) {
            $qb->andWhere($or);
        }

        $qb->orderBy('u.username', 'ASC');

        $this->addPermissionCriteria($qb, $query->getUser(), $query->getTeams());

        return $qb;
    }

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

        $or = $qb->expr()->orX();

        // if no explicit team was requested and the user is part of some teams
        // then find all members of teams where he is teamlead
        if (null !== $user && $user->hasTeamAssignment()) {
            $qb->leftJoin('u.teams', 't');
            $or->add($qb->expr()->eq('t.teamlead', ':teamlead'));
            $qb->setParameter('teamlead', $user);
        }

        // if teams where requested, then select all team members
        if (\count($teams) > 0) {
            $or->add($qb->expr()->isMemberOf(':teams', 'u.teams'));
            $qb->setParameter('teams', $teams);
        }

        // and make sure, that the user himself is always returned
        if (null !== $user) {
            $or->add($qb->expr()->eq('u.id', ':user'));
            $qb->setParameter('user', $user);
        }

        if ($or->count() > 0) {
            $qb->andWhere($or);
        }
    }

    /**
     * @param string $role
     * @return User[]
     * @internal
     */
    public function findUsersWithRole(string $role): array
    {
        if ($role === User::ROLE_USER) {
            return $this->findAll();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->andWhere('u.roles LIKE :role');
        $qb->setParameter('role', '%' . $role . '%');

        return $qb->getQuery()->getResult();
    }

    private function getQueryBuilderForQuery(UserQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.' . $query->getOrderBy(), $query->getOrder())
        ;

        $this->addPermissionCriteria($qb, $query->getCurrentUser(), $query->getTeams());

        if ($query->isShowVisible()) {
            $qb->andWhere($qb->expr()->eq('u.enabled', ':enabled'));
            $qb->setParameter('enabled', true, \PDO::PARAM_BOOL);
        } elseif ($query->isShowHidden()) {
            $qb->andWhere($qb->expr()->eq('u.enabled', ':enabled'));
            $qb->setParameter('enabled', false, \PDO::PARAM_BOOL);
        }

        if ($query->getRole() !== null) {
            $rolesWhere = 'u.roles LIKE :role';
            $qb->setParameter('role', '%' . $query->getRole() . '%');
            // a hack as FOSUserBundle does not save the ROLE_USER in the database as it is the default role
            if ($query->getRole() === User::ROLE_USER) {
                $rolesWhere .= ' OR u.roles LIKE :role1';
                $qb->setParameter('role1', '%{}');
            }
            $qb->andWhere($rolesWhere);
        }

        if ($query->hasSearchTerm()) {
            $searchAnd = $qb->expr()->andX();
            $searchTerm = $query->getSearchTerm();

            foreach ($searchTerm->getSearchFields() as $metaName => $metaValue) {
                $qb->leftJoin('u.preferences', 'meta');
                $searchAnd->add(
                    $qb->expr()->andX(
                        $qb->expr()->eq('meta.name', ':metaName'),
                        $qb->expr()->like('meta.value', ':metaValue')
                    )
                );
                $qb->setParameter('metaName', $metaName);
                $qb->setParameter('metaValue', '%' . $metaValue . '%');
            }

            if ($searchTerm->hasSearchTerm()) {
                $searchAnd->add(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.alias', ':searchTerm'),
                        $qb->expr()->like('u.title', ':searchTerm'),
                        $qb->expr()->like('u.email', ':searchTerm'),
                        $qb->expr()->like('u.username', ':searchTerm')
                    )
                );
                $qb->setParameter('searchTerm', '%' . $searchTerm->getSearchTerm() . '%');
            }

            if ($searchAnd->count() > 0) {
                $qb->andWhere($searchAnd);
            }
        }

        return $qb;
    }

    public function getPagerfantaForQuery(UserQuery $query): Pagerfanta
    {
        $paginator = new Pagerfanta($this->getPaginatorForQuery($query));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }

    public function countUsersForQuery(UserQuery $query): int
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->countDistinct('u.id'))
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    protected function getPaginatorForQuery(UserQuery $query): PaginatorInterface
    {
        $counter = $this->countUsersForQuery($query);
        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new UserLoader($qb->getEntityManager()), $qb, $counter);
    }

    /**
     * @param UserQuery $query
     * @return User[]
     */
    public function getUsersForQuery(UserQuery $query): iterable
    {
        $qb = $this->getQueryBuilderForQuery($query);

        return $this->getHydratedResultsByQuery($qb);
    }

    /**
     * @param QueryBuilder $qb
     * @return User[]
     */
    protected function getHydratedResultsByQuery(QueryBuilder $qb): iterable
    {
        $results = $qb->getQuery()->getResult();

        $loader = new UserLoader($qb->getEntityManager());
        $loader->loadResults($results);

        return $results;
    }
}
