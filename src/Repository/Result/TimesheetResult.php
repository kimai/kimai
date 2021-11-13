<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Result;

use App\Entity\Timesheet;
use App\Repository\Loader\TimesheetLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Query\TimesheetQuery;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

final class TimesheetResult
{
    private $query;
    private $queryBuilder;
    private $statisticCache;
    private $resultCache;

    /**
     * @internal
     */
    public function __construct(TimesheetQuery $query, QueryBuilder $queryBuilder)
    {
        $this->query = $query;
        $this->queryBuilder = $queryBuilder;
    }

    public function getStatistic(): TimesheetResultStatistic
    {
        if ($this->statisticCache === null) {
            $qb = clone $this->queryBuilder;
            $qb
                ->resetDQLPart('select')
                ->resetDQLPart('orderBy')
                ->select('COUNT(t.id) as counter')
                ->addSelect('COALESCE(SUM(t.duration), 0) as duration');

            $result = $qb->getQuery()->getArrayResult()[0];

            $this->statisticCache = new TimesheetResultStatistic($result['counter'], $result['duration']);
        }

        return $this->statisticCache;
    }

    public function toIterable(): iterable
    {
        $query = $this->queryBuilder->getQuery();

        return $query->toIterable();
    }

    /**
     * @param bool $fullyHydrated
     * @return array<Timesheet>
     */
    public function getResults(bool $fullyHydrated = false): array
    {
        // TODO if $fullyHydrated = false and then we call this again using deep nested objects in a second turn
        // it might result in hundreds of lazy loading calls - improve caching
        if ($this->resultCache === null) {
            $query = $this->queryBuilder->getQuery();
            $results = $query->getResult();

            $loader = new TimesheetLoader($this->queryBuilder->getEntityManager(), $fullyHydrated);
            $loader->loadResults($results);

            $this->resultCache = $results;
        }

        return $this->resultCache;
    }

    public function getPagerfanta(bool $fullyHydrated = false): Pagerfanta
    {
        $qb = clone $this->queryBuilder;

        $loader = new LoaderPaginator(new TimesheetLoader($qb->getEntityManager(), $fullyHydrated), $qb, $this->getStatistic()->getCount());
        $paginator = new Pagerfanta($loader);
        $paginator->setMaxPerPage($this->query->getPageSize());
        $paginator->setCurrentPage($this->query->getPage());

        return $paginator;
    }
}
