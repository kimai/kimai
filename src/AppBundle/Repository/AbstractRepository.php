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
     * @param Query $query
     * @param int $page
     * @param int $maxPerPage
     * @return Pagerfanta
     */
    protected function getPager(Query $query, $page = 1, $maxPerPage = 25)
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
