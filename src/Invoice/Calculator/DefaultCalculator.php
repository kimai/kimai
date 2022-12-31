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
 * Class DefaultCalculator works on all given entries using:
 * - the customer currency
 * - the invoice template vat rate
 * - the entries rate
 */
final class DefaultCalculator extends AbstractMergedCalculator implements CalculatorInterface
{
    /**
     * @return InvoiceItem[]
     */
    public function getEntries(): array
    {
        $entries = [];

        foreach ($this->model->getEntries() as $entry) {
            $item = new InvoiceItem();
            $this->mergeInvoiceItems($item, $entry);
            foreach ($entry->getMetaFields() as $field) {
                if ($field->getName() === null) {
                    continue;
                }
                $item->addAdditionalField($field->getName(), $field->getValue());
            }
            $entries[] = $item;
        }

        return $entries;
    }

    public function getId(): string
    {
        return 'default';
    }
}
