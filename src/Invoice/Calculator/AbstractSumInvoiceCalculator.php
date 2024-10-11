<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Entity\ExportableItem;
use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItem;

/**
 * An abstract calculator that sums up the invoice item records.
 */
abstract class AbstractSumInvoiceCalculator extends AbstractMergedCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(ExportableItem $invoiceItem): string
    {
        $ids = $this->getIdentifiers($invoiceItem);

        $identifier = '';
        foreach ($ids as $id) {
            if ($id === null) {
                $id = '__NULL__';
            }
            $identifier .= $id;
        }

        return $identifier;
    }

    /**
     * @param ExportableItem $invoiceItem
     * @return array<int|string|null>
     */
    public function getIdentifiers(ExportableItem $invoiceItem): array
    {
        return [];
    }

    protected function calculateIdentifier(ExportableItem $entry): string
    {
        $prefix = $this->calculateSumIdentifier($entry);

        if (null !== $entry->getFixedRate()) {
            return $prefix . '_fixed_' . (string) $entry->getFixedRate();
        }

        return $prefix . '_hourly_' . (string) $entry->getHourlyRate();
    }

    /**
     * @return InvoiceItem[]
     */
    public function getEntries(): array
    {
        $entries = $this->model->getEntries();
        if (empty($entries)) {
            return [];
        }

        /** @var InvoiceItem[] $invoiceItems */
        $invoiceItems = [];

        foreach ($entries as $entry) {
            $id = $this->calculateIdentifier($entry);

            if (!isset($invoiceItems[$id])) {
                $invoiceItems[$id] = new InvoiceItem();
            }
            $invoiceItem = $invoiceItems[$id];
            $this->mergeInvoiceItems($invoiceItem, $entry);
            $this->mergeSumInvoiceItem($invoiceItem, $entry);
        }

        return $this->sortEntries(array_values($invoiceItems));
    }

    /**
     * @param InvoiceItem $invoiceItem
     * @param ExportableItem $entry
     * @return void
     */
    protected function mergeSumInvoiceItem(InvoiceItem $invoiceItem, ExportableItem $entry): void
    {
    }
}
