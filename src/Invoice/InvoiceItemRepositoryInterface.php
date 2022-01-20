<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Invoice;
use App\Repository\Query\InvoiceQuery;

interface InvoiceItemRepositoryInterface
{
    // public function saveInvoice(Invoice $invoice, InvoiceModel $model): void;

    /**
     * @param InvoiceItemInterface[] $invoiceItems
     * @deprecated since 1.17 - use saveInvoice() instead
     * @return void
     */
    public function setExported(array $invoiceItems) /* : void */;

    /**
     * @param InvoiceQuery $query
     * @return InvoiceItemInterface[]
     */
    public function getInvoiceItemsForQuery(InvoiceQuery $query): iterable;
}
