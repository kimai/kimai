<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Paginator;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

final class QueryBuilderPaginator implements PaginatorInterface
{
    private QueryBuilder $query;
    private int $results;

    public function __construct(QueryBuilder $query, int $results)
    {
        $this->query = $query;
        $this->results = $results;
    }

    public function getNbResults(): int
    {
        return $this->results;
    }

    public function getSlice(int $offset, int $length): iterable
    {
        $query = $this->query
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->getResults($query);
    }

    private function getResults(Query $query)
    {
        return $query->execute();
    }

    public function getAll(): iterable
    {
        return $this->getResults($this->query->getQuery());
    }
}
