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

final class LoaderPaginator implements PaginatorInterface
{
    public function __construct(private LoaderInterface $loader, private QueryBuilder $query, private int $results)
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
            ->getQuery()
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

        $this->loader->loadResults($results);

        return $results; // @phpstan-ignore-line
    }

    public function getAll(): iterable
    {
        return $this->getResults($this->query->getQuery());
    }
}
