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

/**
 * @deprecated use QueryPaginator instead
 * @implements PaginatorInterface<mixed>
 */
final class QueryBuilderPaginator implements PaginatorInterface
{
    /**
     * @param int<0, max> $results
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly int $results
    )
    {
    }

    public function getNbResults(): int
    {
        return $this->results;
    }

    /**
     * @return iterable<array-key, mixed>
     */
    public function getSlice(int $offset, int $length): iterable
    {
        /** @var Query<null, mixed> $query */
        $query = $this->queryBuilder
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->getResults($query);
    }

    /**
     * @param Query<null, mixed> $query
     * @return iterable<array-key, mixed>
     */
    private function getResults(Query $query): iterable
    {
        return $query->execute(); // @phpstan-ignore-line
    }

    /**
     * @return iterable<array-key, mixed>
     */
    public function getAll(): iterable
    {
        return $this->getResults($this->queryBuilder->getQuery());
    }
}
