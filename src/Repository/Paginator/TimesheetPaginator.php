<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Paginator;

use App\Repository\Loader\TimesheetLoader;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\AdapterInterface;

final class TimesheetPaginator implements AdapterInterface
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
     * @var TimesheetLoader
     */
    private $loader;

    public function __construct(QueryBuilder $query, int $results)
    {
        $this->query = $query;
        $this->results = $results;
        $this->loader = new TimesheetLoader($query->getEntityManager());
    }

    public function setPreloadMetaFields(bool $preload): TimesheetPaginator
    {
        $this->loader->setPreloadMetaFields($preload);

        return $this;
    }

    public function setPreloadUser(bool $preload): TimesheetPaginator
    {
        $this->loader->setPreloadUser($preload);

        return $this;
    }

    public function setPreloadTags(bool $preload): TimesheetPaginator
    {
        $this->loader->setPreloadTags($preload);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return $this->results;
    }

    private function getResults(Query $query)
    {
        $results = $query->execute();

        $this->loader->loadResults($results);

        return $results;
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

    public function getAll()
    {
        return $this->getResults($this->query->getQuery());
    }
}
