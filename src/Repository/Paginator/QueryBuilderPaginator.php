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
    /**
     * @var QueryBuilder
     */
    private $query;
    /**
     * @var int
     */
    private $results = 0;

    public function __construct(QueryBuilder $query, int $results)
    {
        $this->query = $query;
        $this->results = $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return $this->results;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
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
