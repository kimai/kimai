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

final class LoaderQueryPaginator implements PaginatorInterface
{
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
     * @return iterable<array-key, iterable<mixed>>
     */
    public function getSlice(int $offset, int $length): iterable
    {
        $query = $this->query
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->getResults($query);
    }

    /**
     * @param Query<null, mixed> $query
     * @return iterable<array-key, iterable<mixed>>
     */
    private function getResults(Query $query)
    {
        $results = $query->execute();

        // TODO should this be cached?
        $this->loader->loadResults($results);

        return $results; // @phpstan-ignore-line
    }

    public function getAll(): iterable
    {
        return $this->getResults($this->query);
    }
}
