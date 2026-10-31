<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\ExportableItem;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Repository\CustomerRepository;
use App\Repository\Query\ExportQuery;
use App\Repository\Query\TimesheetQueryHint;
use App\Repository\TimesheetRepository;
use Doctrine\ORM\Query\Expr\Join;

final class TimesheetExportRepository implements ExportRepositoryInterface, ExportableRepositoryInterface
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        private readonly CustomerRepository $customerRepository,
    )
    {
    }

    /**
     * @param Timesheet[] $items
     */
    public function setExported(array $items): void
    {
        $timesheets = [];

        foreach ($items as $item) {
            if ($item instanceof Timesheet) {
                $timesheets[] = $item;
            }
        }

        if (empty($timesheets)) {
            return;
        }

        $this->repository->setExported($timesheets);
    }

    /**
     * @return iterable<ExportableItem>
     */
    public function getExportItemsForQuery(ExportQuery $query): iterable
    {
        return $this->getResult($query);
    }

    public function getType(): string
    {
        return 'timesheet';
    }

    /**
     * @return array<ExportableResultStatistic>
     */
    private function getStatistic(ExportQuery $query): array
    {
        $qb = $this->repository->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select('COUNT(t.id) as counter')
            ->addSelect('IDENTITY(p.customer) as customer')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) as rate')
            ->leftJoin('t.project', 'p')
            ->groupBy('p.customer')
        ;

        if (!\in_array('p', $qb->getAllAliases(), true)) {
        }

        /** @var array<int, array{duration: non-negative-int, counter: non-negative-int, customer: non-negative-int, rate: float}> $results */
        $results = $qb->getQuery()->getResult(); // @phpstan-ignore-line doctrine.dql
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result['customer'];
        }

        $customers = [];
        foreach ($this->customerRepository->findByIds($ids) as $customer) {
            $customers[$customer->getId()] = $customer;
        }

        $statistics = [];
        foreach ($results as $result) {
            $statistics[] = new ExportableResultStatistic(
                $customers[$result['customer']],
                $result['counter'],
                $result['duration'],
                $result['rate'],
            );
        }

        return $statistics;
    }

    public function getPreview(ExportQuery $query, int $maxItems): ExportableResultInterface
    {
        $statistics = $this->getStatistic($query);
        $query = clone $query;
        $query->setMaxResults($maxItems);

        return new ExportablePreviewResult($this->getResult($query), $statistics);
    }

    /**
     * @return array<ExportableItem>
     */
    public function getResult(ExportQuery $query): array
    {
        $query->addQueryHint(TimesheetQueryHint::CUSTOMER_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::PROJECT_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::ACTIVITY_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::USER_PREFERENCES);

        return $this->repository->getTimesheetResult($query)->getResults();
    }
}
