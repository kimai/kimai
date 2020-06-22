<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Repository\Query\InvoiceQuery;

interface InvoiceItemRepositoryInterface
{
    /**
     * @param InvoiceItemInterface[] $invoiceItems
     */
    public function setExported(array $invoiceItems);

    /**
     * @param InvoiceQuery $query
     * @return InvoiceItemInterface[]
     */
    public function getInvoiceItemsForQuery(InvoiceQuery $query): iterable;
}
