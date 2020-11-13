<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\NumberGenerator;

use App\Invoice\InvoiceModel;
use App\Invoice\NumberGeneratorInterface;

class IncrementingNumberGenerator implements NumberGeneratorInterface
{
    private $counter = 0;

    /**
     * @param InvoiceModel $model
     */
    public function setModel(InvoiceModel $model)
    {
    }

    /**
     * @return string
     */
    public function getInvoiceNumber(): string
    {
        return $this->counter++;
    }

    /**
     * Returns the unique ID of this number generator.
     *
     * Prefix it with your company name followed by a hyphen (e.g. "acme-"),
     * if this is a third-party generator.
     *
     * @return string
     */
    public function getId(): string
    {
        return 'testing';
    }
}
