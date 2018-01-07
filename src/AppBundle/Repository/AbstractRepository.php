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

use AppBundle\Repository\Query\BaseQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class AbstractRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
abstract class AbstractRepository extends EntityRepository
{
    /**
     * @param QueryBuilder $qb
     * @param BaseQuery $query
     * @return QueryBuilder|Pagerfanta
     */
    protected function getBaseQueryResult(QueryBuilder $qb, BaseQuery $query)
    {
        if ($query->getResultType() == BaseQuery::RESULT_TYPE_PAGER) {
            return $this->getPager($qb->getQuery(), $query->getPage(), $query->getPageSize());
        }

        return $qb;
    }

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
