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
use App\Utils\Pagination;
use Doctrine\ORM\QueryBuilder;

final class TimesheetResult
{
    private ?TimesheetResultStatistic $statisticCache = null;
    private bool $cachedFullyHydrated = false;
    /**
     * @var array<Timesheet>|null
     */
    private ?array $resultCache = null;

    /**
     * @internal
     */
    public function __construct(private TimesheetQuery $query, private QueryBuilder $queryBuilder)
    {
    }

    public function getStatistic(): TimesheetResultStatistic
    {
        if ($this->statisticCache === null) {
            $withDuration = $this->query->countFilter() > 0;
            $qb = clone $this->queryBuilder;
            $qb
                ->resetDQLPart('select')
                ->resetDQLPart('orderBy')
                ->select('COUNT(t.id) as counter')
            ;

            if ($withDuration) {
                $qb->addSelect('COALESCE(SUM(t.duration), 0) as duration');
            }

            $result = $qb->getQuery()->getArrayResult()[0];
            $duration = $withDuration ? $result['duration'] : 0;

            $this->statisticCache = new TimesheetResultStatistic($result['counter'], $duration);
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
        if ($this->resultCache === null || ($fullyHydrated && $this->cachedFullyHydrated === false)) {
            $query = $this->queryBuilder->getQuery();
            $results = $query->getResult();

            $loader = new TimesheetLoader($this->queryBuilder->getEntityManager(), $fullyHydrated);
            $loader->loadResults($results);

            $this->cachedFullyHydrated = $fullyHydrated;
            $this->resultCache = $results;
        }

        return $this->resultCache;
    }

    public function getPagerfanta(bool $fullyHydrated = false): Pagination
    {
        $qb = clone $this->queryBuilder;

        $loader = new LoaderPaginator(new TimesheetLoader($qb->getEntityManager(), $fullyHydrated), $qb, $this->getStatistic()->getCount());
        $paginator = new Pagination($loader);
        $paginator->setMaxPerPage($this->query->getPageSize());
        $paginator->setCurrentPage($this->query->getPage());

        return $paginator;
    }
}
