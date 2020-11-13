<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Timesheet;
use App\Invoice\InvoiceItemInterface;
use App\Invoice\InvoiceItemRepositoryInterface;
use App\Repository\Query\InvoiceQuery;

final class TimesheetInvoiceItemRepository implements InvoiceItemRepositoryInterface
{
    /**
     * @var TimesheetRepository
     */
    private $repository;

    public function __construct(TimesheetRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceItemInterface[]
     */
    public function getInvoiceItemsForQuery(InvoiceQuery $query): iterable
    {
        return $this->repository->getTimesheetsForQuery($query);
    }

    /**
     * @param Timesheet[] $invoiceItems
     */
    public function setExported(array $invoiceItems)
    {
        foreach ($invoiceItems as $item) {
            if (!$item instanceof Timesheet) {
                throw new \InvalidArgumentException('TimesheetInvoiceItemRepository only supports Timesheet entities');
            }
        }

        $this->repository->setExported($invoiceItems);
    }
}
