<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\ExportableItem;
use App\Entity\Timesheet;
use App\Invoice\InvoiceItemRepositoryInterface;
use App\Repository\Query\InvoiceQuery;

final class TimesheetInvoiceItemRepository implements InvoiceItemRepositoryInterface
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    /**
     * @param InvoiceQuery $query
     * @return ExportableItem[]
     */
    public function getInvoiceItemsForQuery(InvoiceQuery $query): iterable
    {
        return $this->repository->getTimesheetsForQuery($query, true);
    }

    /**
     * @param ExportableItem[] $invoiceItems
     */
    public function setExported(array $invoiceItems): void
    {
        $timesheets = [];

        foreach ($invoiceItems as $item) {
            if ($item instanceof Timesheet) {
                $timesheets[] = $item;
            }
        }

        if (empty($timesheets)) {
            return;
        }

        $this->repository->setExported($timesheets);
    }
}
