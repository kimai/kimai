<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Tax;

final class TaxRow
{
    public function __construct(
        private readonly Tax $tax,
        private readonly float $basePrice = 0.0
    )
    {
    }

    public function getTax(): Tax
    {
        return $this->tax;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function getAmount(): float
    {
        $percent = $this->tax->getRate() / 100.00;

        return round($this->basePrice * $percent, 4);
    }
}
