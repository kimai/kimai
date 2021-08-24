<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Result;

use App\Repository\Loader\TimesheetLoader;
use App\Repository\Query\TimesheetQuery;
use Doctrine\ORM\QueryBuilder;

class TimesheetResult
{
    private $fullyHydrated = false;
    private $query;
    private $queryBuilder;

    public function __construct(TimesheetQuery $query, QueryBuilder $queryBuilder)
    {
        $this->query = $query;
        $this->queryBuilder = $queryBuilder;
    }

    public function setFullyHydrated(bool $fullyHydrated): void
    {
        $this->fullyHydrated = $fullyHydrated;
    }

    public function getStatistic(): TimesheetResultStatistic
    {
        $qb = clone $this->queryBuilder;
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select('COUNT(t.id) as counter')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
        ;

        $result = $qb->getQuery()->getArrayResult()[0];

        return new TimesheetResultStatistic($result['counter'], $result['duration']);
    }

    public function toIterable(): iterable
    {
        $query = $this->queryBuilder->getQuery();

        return $query->toIterable();
    }

    public function getResults(): array
    {
        $query = $this->queryBuilder->getQuery();
        $results = $query->getResult();

        $loader = new TimesheetLoader($this->queryBuilder->getEntityManager(), $this->fullyHydrated);
        $loader->loadResults($results);

        return $results;
    }
}
