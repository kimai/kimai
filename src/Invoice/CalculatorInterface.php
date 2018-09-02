<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Timesheet;
use App\Model\InvoiceModel;

/**
 * CalculatorInterface defines all methods for any invoice price calculator.
 */
interface CalculatorInterface
{
    /**
     * Return the timesheet records that will be displayed on the invoice.
     *
     * @return Timesheet[]
     */
    public function getEntries();

    /**
     * Set the invoice model and can be used to fetch the customer.
     *
     * @param InvoiceModel $model
     */
    public function setModel(InvoiceModel $model);

    /**
     * Returns the subtotal before taxes.
     *
     * @return float
     */
    public function getSubtotal(): float;

    /**
     * Returns the tax amount for this invoice.
     *
     * @return float
     */
    public function getTax(): float;

    /**
     * Returns the total amount for this invoice including taxes.
     *
     * @return float
     */
    public function getTotal(): float;

    /**
     * Returns the currency for the invoices amounts.
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Returns the percentage for the value-added tax (VAT) calculation.
     *
     * @return float
     */
    public function getVat(): ?float;

    /**
     * Returns the total amount of worked time in seconds.
     *
     * @return int
     */
    public function getTimeWorked(): int;
}
