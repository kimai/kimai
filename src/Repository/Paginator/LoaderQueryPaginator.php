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

/**
 * @template T
 * @implements PaginatorInterface<T>
 */
final class LoaderQueryPaginator implements PaginatorInterface
{
    /**
     * @param LoaderInterface<T> $loader
     * @param Query<T> $query
     * @param int<0, max> $results
     */
    public function __construct(
        private readonly LoaderInterface $loader,
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
        /** @var Query<null, T> $query */
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
        /** @var array<T> $results */
        $results = $query->execute();

        $this->loader->loadResults($results);

        return $results;
    }

    /**
     * @return iterable<array-key, T>
     */
    public function getAll(): iterable
    {
        return $this->getResults($this->query);
    }
}
