<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Repository;

use AppBundle\Entity\User;
use TimesheetBundle\Entity\Timesheet;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class ActivityRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityRepository extends EntityRepository
{

    /**
     * @param User $user
     * @return Query
     */
    public function queryLatest(User $user = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a')
            ->from('TimesheetBundle:Activity', 'a')
            ->orderBy('a.name', 'DESC');

        return $qb->getQuery();
    }

    /**
     * @param User $user
     * @param int $page
     * @return Pagerfanta
     */
    public function findLatest(User $user, $page = 1)
    {
        return $this->getPager($this->queryLatest($user), $page);
    }

    /**
     * @param int $page
     *
     * @return Pagerfanta
     */
    public function findAll($page = 1)
    {
        return $this->getPager($this->queryLatest(), $page);
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
