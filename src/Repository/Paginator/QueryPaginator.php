<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Paginator;

use Doctrine\ORM\Query;

/**
 * @template T
 * @implements PaginatorInterface<T>
 */
final class QueryPaginator implements PaginatorInterface
{
    /**
     * @param Query<null, T> $query
     * @param int<0, max> $results
     */
    public function __construct(
        private readonly Query $query,
        private readonly int $results
    )
    {
    }

    public function getNbResults(): int
    {
        return $this->results;
    }

    /**
     * @return iterable<array-key, T>
     */
    public function getSlice(int $offset, int $length): iterable
    {
        $query = $this->query
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->getResults($query);
    }

    /**
     * @param Query<null, T> $query
     * @return iterable<array-key, T>
     */
    private function getResults(Query $query): iterable
    {
        return $query->execute(); // @phpstan-ignore-line
    }

    /**
     * @return iterable<array-key, T>
     */
    public function getAll(): iterable
    {
        return $this->getResults($this->query);
    }
}
