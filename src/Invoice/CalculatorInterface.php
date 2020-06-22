<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

/**
 * CalculatorInterface defines all methods for any invoice price calculator.
 */
interface CalculatorInterface
{
    /**
     * Return the invoice items that will be displayed on the invoice.
     *
     * @return InvoiceItem[]
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
     * @deprecated since 1.8 will be removed with 2.0
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

    /**
     * Returns the unique ID of this calculator.
     *
     * Prefix it with your company name followed by a hyphen (e.g. "acme-"),
     * if this is a third-party calculator.
     *
     * @return string
     */
    public function getId(): string;
}
