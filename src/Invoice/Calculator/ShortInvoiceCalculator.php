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
 * A calculator that sums up all invoice item records from the model and returns only one
 * entry for a compact invoice version.
 */
class ShortInvoiceCalculator extends AbstractMergedCalculator implements CalculatorInterface
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

        $invoiceItem = new InvoiceItem();
        $keys = [];

        foreach ($entries as $entry) {
            $key = 'hourly_' . (string) $entry->getHourlyRate();
            if (null !== $entry->getFixedRate()) {
                $key = 'fixed_' . (string) $entry->getFixedRate();
            }
            if (!\in_array($key, $keys)) {
                $keys[] = $key;
            }
            $this->mergeInvoiceItems($invoiceItem, $entry);
        }

        if (\count($keys) > 1) {
            $invoiceItem->setAmount(1);
            $invoiceItem->setFixedRate($invoiceItem->getRate());
            $invoiceItem->setHourlyRate($invoiceItem->getRate());
        }

        return [$invoiceItem];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'short';
    }
}
