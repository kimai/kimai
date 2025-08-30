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
use App\Repository\Query\TimesheetQueryHint;

final class TimesheetInvoiceItemRepository implements InvoiceItemRepositoryInterface
{
    public function __construct(private readonly TimesheetRepository $repository)
    {
    }

    /**
     * @return ExportableItem[]
     */
    public function getInvoiceItemsForQuery(InvoiceQuery $query): iterable
    {
        $query->addQueryHint(TimesheetQueryHint::CUSTOMER_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::PROJECT_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::ACTIVITY_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::USER_PREFERENCES);

        return $this->repository->getTimesheetResult($query)->getResults();
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
