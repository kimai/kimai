<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use App\Repository\Query\UserQuery;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

class UserRepository extends AbstractRepository implements UserLoaderInterface
{
    /**
     * @param $id
     * @return null|User
     */
    public function getById($id)
    {
        return $this->find($id);
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
            ->select('u', 'p')
            ->leftJoin('u.preferences', 'p')
            ->where('u.username = :username')
            ->orWhere('u.email = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getSingleResult();
    }
}
