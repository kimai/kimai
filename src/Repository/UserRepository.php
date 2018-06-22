<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use App\Model\UserStatistic;
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
     * Return statistic data for all user.
     *
     * @return UserStatistic
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getGlobalStatistics()
    {
        $countAll = $this->getEntityManager()
            ->createQuery('SELECT COUNT(u.id) FROM ' . User::class . ' u')
            ->getSingleScalarResult();

        $stats = new UserStatistic();
        $stats->setTotalAmount($countAll);

        return $stats;
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
            $qb->andWhere('u.active = 1');
        } elseif (UserQuery::SHOW_HIDDEN == $query->getVisibility()) {
            $qb->andWhere('u.active = 0');
        }

        if ($query->getRole() !== null) {
            $qb->andWhere('u.roles LIKE :role')->setParameter('role', '%' . $query->getRole() . '%');
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
