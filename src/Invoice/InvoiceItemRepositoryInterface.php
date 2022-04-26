<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\ExportableItem;
use App\Repository\Query\InvoiceQuery;

interface InvoiceItemRepositoryInterface
{
    /**
     * @param ExportableItem[] $invoiceItems
     * @return void
     */
    public function setExported(array $invoiceItems) /* : void */;

    /**
     * @param InvoiceQuery $query
     * @return ExportableItem[]
     */
    public function getInvoiceItemsForQuery(InvoiceQuery $query): iterable;
}
