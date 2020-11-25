<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItemInterface;

/**
 * Calculator for filtering activities and dates.
 */
class DateActivityInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(InvoiceItemInterface $invoiceItem): string
    {
        // Fetch entries from activites
        $activity = $invoiceItem->getActivity()->getId();
        // Splits string into array
        $activity_array = explode(" ", $activity);

        // Fetch all entries from timesheet
        $date = $invoiceItem->getBegin()->format('Y-m-d');
        // Splits string into array
        $date_array = explode(" ", $date);

        // Merges both arrays together
        $activity_date_array = array_merge($date_array, $activity_array);
        // Creates from array a readable string for Kimai
        $activity_date_string = implode(" ", $activity_date_array);

        // Returns the new created string
        return $activity_date_string;
    }

    /**
     * Id for creating invoice with this calculator.
     */
    public function getId(): string
    {
        return 'date-activity';
    }
}
