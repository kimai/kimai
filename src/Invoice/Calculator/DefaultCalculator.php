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
 * - the customers currency
 * - the invoice template vat rate
 * - the entries rate
 */
class DefaultCalculator extends AbstractMergedCalculator implements CalculatorInterface
{
    /**
     * @return InvoiceItem[]
     */
    public function getEntries()
    {
        $entries = [];

        foreach ($this->model->getEntries() as $entry) {
            $item = new InvoiceItem();
            $this->mergeInvoiceItems($item, $entry);
            foreach ($entry->getVisibleMetaFields() as $field) {
                $item->addAdditionalField($field->getName(), $field->getValue());
            }
            $entries[] = $item;
        }

        return $entries;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'default';
    }
}
