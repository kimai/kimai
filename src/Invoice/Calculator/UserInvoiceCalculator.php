<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItem;

/**
 * A calculator that sums up the timesheet records by user.
 */
class UserInvoiceCalculator extends AbstractMergedCalculator implements CalculatorInterface
{
    /**
     * @return InvoiceItem[]
     */
    public function getEntries()
    {
        $entries = $this->model->getEntries();
        if (empty($entries)) {
            return [];
        }

        /** @var InvoiceItem[] $invoiceItems */
        $invoiceItems = [];

        foreach ($entries as $entry) {
            $id = $entry->getUser()->getId();
            if (!isset($invoiceItems[$id])) {
                $invoiceItems[$id] = new InvoiceItem();
            }
            $invoiceItem = $invoiceItems[$id];
            $this->mergeTimesheets($invoiceItem, $entry);
        }

        return array_values($invoiceItems);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'user';
    }
}
