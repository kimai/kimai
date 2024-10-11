<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Paginator;

use App\Repository\Loader\LoaderInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * @template T
 * @implements PaginatorInterface<T>
 */
final class LoaderPaginator implements PaginatorInterface
{
    /**
     * @param LoaderInterface<T> $loader
     * @param int<0, max> $results
     */
    public function __construct(
        private readonly LoaderInterface $loader,
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
     * @return Query<null, T>
     */
    private function getQuery(): Query
    {
        return $this->queryBuilder->getQuery(); // @phpstan-ignore-line
    }

    /**
     * @return iterable<array-key, T>
     */
    public function getSlice(int $offset, int $length): iterable
    {
        /** @var Query<null, T> $query */
        $query = $this->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->getResults($query);
    }

    /**
     * @param Query<null, T> $query
     * @return array<array-key, T>
     */
    private function getResults(Query $query): array
    {
        /** @var array<array-key, T> $results */
        $results = $query->execute();

        $this->loader->loadResults($results);

        return $results;
    }

    /**
     * @return iterable<array-key, T>
     */
    public function getAll(): iterable
    {
        return $this->getResults($this->getQuery());
    }
}
