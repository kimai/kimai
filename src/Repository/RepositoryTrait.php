<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Repository\Query\BaseQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Trait RepositoryTrait
 */
trait RepositoryTrait
{
    /**
     * @param QueryBuilder $qb
     * @param BaseQuery $query
     * @return QueryBuilder|Pagerfanta|array
     */
    protected function getBaseQueryResult(QueryBuilder $qb, BaseQuery $query)
    {
        if (BaseQuery::RESULT_TYPE_PAGER === $query->getResultType()) {
            return $this->getPager($qb->getQuery(), $query->getPage(), $query->getPageSize());
        } elseif (BaseQuery::RESULT_TYPE_OBJECTS === $query->getResultType()) {
            return $qb->getQuery()->execute();
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
