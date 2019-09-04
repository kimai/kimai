<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use App\Repository\Query\UserFormTypeQuery;
use App\Repository\Query\UserQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

class UserRepository extends EntityRepository implements UserLoaderInterface
{
    use RepositoryTrait;

    public function getById($id): ?User
    {
        @trigger_error('UserRepository::getById is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return $this->getUserById($id);
    }

    /**
     * Used to fetch the currently logged-in user.
     *
     * @param int $id
     * @return null|User
     */
    public function getUserById($id): ?User
    {
        try {
            return $this->createQueryBuilder('u')
                ->select('u', 'p', 't', 'tu', 'tl')
                ->leftJoin('u.preferences', 'p')
                ->leftJoin('u.teams', 't')
                ->leftJoin('t.users', 'tu')
                ->leftJoin('t.teamlead', 'tl')
                ->where('u.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (\Exception $ex) {
        }

        return null;
    }

    /**
     * Overwritten to fetch preferences when using the Profile controller actions.
     * Depends on the query, some magic mechanisms like the ParamConverter will use this method to fetch the user.
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        if (count($criteria) == 1 && isset($criteria['username'])) {
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
     */
    public function findByQuery(UserQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.' . $query->getOrderBy(), $query->getOrder());

        if (UserQuery::SHOW_VISIBLE == $query->getVisibility()) {
            $qb->andWhere($qb->expr()->eq('u.enabled', ':enabled'));
            $qb->setParameter('enabled', true, \PDO::PARAM_BOOL);
        } elseif (UserQuery::SHOW_HIDDEN == $query->getVisibility()) {
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

        return $this->getBaseQueryResult($qb, $query);
    }

    /**
     * @param string $username
     * @return mixed|null|\Symfony\Component\Security\Core\User\UserInterface
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
            ->getSingleResult();
    }

    public function getQueryBuilderForFormType(UserFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $qb->andWhere($qb->expr()->eq('u.enabled', ':enabled'));
        $qb->setParameter('enabled', true, \PDO::PARAM_BOOL);

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
        if (null !== $user && ($user->isSuperAdmin() || $user->isAdmin())) {
            return;
        }

        if (null !== $user) {
            $qb->leftJoin('u.teams', 'teams')
                ->leftJoin('teams.users', 'users')
                ->andWhere('teams.teamlead = :id')
                ->setParameter('id', $user);
        }
    }
}
