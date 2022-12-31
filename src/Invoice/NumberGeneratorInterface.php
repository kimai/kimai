<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

/**
 * Class NumberGeneratorInterface defines all methods that invoice number generator have to implement.
 */
interface NumberGeneratorInterface
{
    public function setModel(InvoiceModel $model): void;

    public function getInvoiceNumber(): string;

    /**
     * Returns the unique ID of this number generator.
     *
     * Prefix it with your company name followed by a hyphen (e.g. "acme-"),
     * if this is a third-party generator.
     *
     * @return string
     */
    public function getId(): string;
}
