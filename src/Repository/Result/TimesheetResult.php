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
use App\Repository\Paginator\LoaderQueryPaginator;
use App\Repository\Query\TimesheetQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * @internal
 */
final class TimesheetResult
{
    private ?TimesheetResultStatistic $statisticCache = null;
    /**
     * @var array<Timesheet>|null
     */
    private ?array $resultCache = null;

    /**
     * @internal
     * @param Query<null, Timesheet> $query
     */
    public function __construct(
        private readonly TimesheetQuery $timesheetQuery,
        private readonly EntityManagerInterface $entityManager,
        private readonly QueryBuilder $statisticQb,
        private readonly Query $query
    )
    {
    }

    public function getStatistic(): TimesheetResultStatistic
    {
        if ($this->statisticCache === null) {
            $withDuration = $this->timesheetQuery->countFilter() > 0;
            $qb = clone $this->statisticQb;
            $qb
                ->resetDQLPart('select')
                ->resetDQLPart('orderBy')
                ->select('COUNT(t.id) as counter')
            ;

            if ($withDuration) {
                $qb->addSelect('COALESCE(SUM(t.duration), 0) as duration');
            }

            /** @var array{'duration': int<0, max>, 'counter': int<0, max>} $result */
            $result = $qb->getQuery()->getArrayResult()[0];
            $duration = $withDuration ? $result['duration'] : 0;

            $this->statisticCache = new TimesheetResultStatistic($result['counter'], $duration);
        }

        return $this->statisticCache;
    }

    /**
     * @return iterable<Timesheet>
     */
    public function toIterable(): iterable
    {
        return $this->query->toIterable();
    }

    /**
     * @return array<Timesheet>
     */
    public function getResults(): array
    {
        if ($this->resultCache === null) {
            /** @var array<Timesheet> $results */
            $results = $this->query->getResult();

            $loader = new TimesheetLoader($this->entityManager, $this->timesheetQuery);
            $loader->loadResults($results);

            $this->resultCache = $results;
        }

        return $this->resultCache;
    }

    public function getPagerfanta(): Pagination
    {
        $loader = new LoaderQueryPaginator(new TimesheetLoader($this->entityManager, $this->timesheetQuery), $this->query, $this->getStatistic()->getCount());

        $paginator = new Pagination($loader);
        $paginator->setMaxPerPage($this->timesheetQuery->getPageSize());
        $paginator->setCurrentPage($this->timesheetQuery->getPage());

        return $paginator;
    }
}
