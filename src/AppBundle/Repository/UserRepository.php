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
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class UserRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserRepository extends EntityRepository
{

    /**
     * Return statistic data for all user.
     *
     * @return UserStatistic
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

    /**
     * @return Query
     */
    protected function queryAll()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u')
            ->from('AppBundle:User', 'u')
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }

    public function findByUsername($username)
    {
        return $this->findOneBy(['']);
    }

    /**
     * @param int $page
     *
     * @return Pagerfanta
     */
    public function findAll($page = 1)
    {
        return $this->getPager($this->queryAll(), $page);
    }

    /**
     * @param Query $query
     * @param int $page
     * @return Pagerfanta
     */
    protected function getPager(Query $query, $page = 1)
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage(25);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
