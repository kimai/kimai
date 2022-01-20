<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Timesheet;
use App\Invoice\InvoiceItemInterface;
use App\Invoice\InvoiceItemRepositoryInterface;
use App\Invoice\InvoiceModel;
use App\Repository\Query\InvoiceQuery;

/**
 * @internal only to be used by the invoice system
 */
final class TimesheetInvoiceItemRepository implements InvoiceItemRepositoryInterface
{
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
        return $this->repository->getTimesheetsForQuery($query, true);
    }

    /**
     * @param InvoiceItemInterface[] $invoiceItems
     * @deprecated since 1.17 - use saveInvoice() instead
     */
    public function setExported(array $invoiceItems)
    {
        @trigger_error('TimesheetInvoiceItemRepository::setExported() is deprecated and will be removed with 2.0, use saveInvoice() instead.', E_USER_DEPRECATED);

        $timesheets = [];

        foreach ($invoiceItems as $item) {
            if ($item instanceof Timesheet) {
                $item->setExported(true);
                $timesheets[] = $item;
            }
        }

        if (\count($timesheets) > 0) {
            $this->repository->saveMultiple($timesheets);
        }
    }

    public function saveInvoice(Invoice $invoice, InvoiceModel $model): void
    {
        $setExported = $model->getQuery()->isMarkAsExported();

        $timesheets = [];

        foreach ($model->getEntries() as $item) {
            if ($item instanceof Timesheet) {
                if ($setExported) {
                    $item->setExported(true);
                }
                $item->addInvoice($invoice);
                $timesheets[] = $item;
            }
        }

        if (\count($timesheets) > 0) {
            $this->repository->saveMultiple($timesheets);
        }
    }
}
