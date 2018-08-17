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

/**
 * Class UserRepository
 */
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
     * @return int
     */
    public function countUser()
    {
        return $this->count([]);
    }

    /**
     * @param UserQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function findByQuery(UserQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // if we join activities, the maxperpage limit will limit the list to the amount or projects + activties
        $qb->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.' . $query->getOrderBy(), $query->getOrder());

        if (UserQuery::SHOW_VISIBLE == $query->getVisibility()) {
            $qb->andWhere('u.enabled = 1');
        } elseif (UserQuery::SHOW_HIDDEN == $query->getVisibility()) {
            $qb->andWhere('u.enabled = 0');
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

        return $this->getPager($qb->getQuery(), $query->getPage(), $query->getPageSize());
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
            ->where('u.username = :username')
            ->orWhere('u.email = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getSingleResult();
    }
}
