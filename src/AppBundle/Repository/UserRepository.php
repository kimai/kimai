<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Repository;

use AppBundle\Model\UserStatistic;
use AppBundle\Repository\Query\UserQuery;

/**
 * Class UserRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserRepository extends AbstractRepository
{

    /**
     * Return statistic data for all user.
     *
     * @return UserStatistic
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getGlobalStatistics()
    {
        $countAll = $this->getEntityManager()
            ->createQuery('SELECT COUNT(u.id) FROM AppBundle:User u')
            ->getSingleScalarResult();

        $stats = new UserStatistic();
        $stats->setTotalAmount($countAll);
        return $stats;
    }

    public function findByUsername($username)
    {
        return $this->findOneBy(['username' => $username]);
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
            ->from('AppBundle:User', 'u')
            ->orderBy('u.' . $query->getOrderBy(), $query->getOrder());

        if ($query->getVisibility() === UserQuery::SHOW_VISIBLE) {
            $qb->andWhere('u.visible = 1');
        } elseif ($query->getVisibility() === UserQuery::SHOW_HIDDEN) {
            $qb->andWhere('u.visible = 0');
        }

        return $this->getPager($qb->getQuery(), $query->getPage(), $query->getPageSize());
    }
}
