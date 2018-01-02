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
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Entity\Timesheet;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use TimesheetBundle\Model\ActivityStatistic;

/**
 * Class AbstractRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
abstract class AbstractRepository extends EntityRepository
{

    /**
     * @param string $orderBy
     * @return Query
     */
    protected function queryAll($orderBy = 'id')
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a')
            ->from($this->getEntityName(), 'a')
            ->orderBy('a.' . $orderBy, 'ASC');

        return $qb->getQuery();
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
