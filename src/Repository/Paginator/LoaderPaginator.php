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
    /**
     * @var QueryBuilder
     */
    private $query;
    /**
     * @var int
     */
    private $results = 0;
    /**
     * @var LoaderInterface
     */
    private $loader;

    public function __construct(LoaderInterface $loader, QueryBuilder $query, int $results)
    {
        $this->loader = $loader;
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
        $results = $query->execute();

        $this->loader->loadResults($results);

        return $results;
    }

    public function getAll(): iterable
    {
        return $this->getResults($this->query->getQuery());
    }
}
