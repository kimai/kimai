<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

final class Revenue
{
    public function __construct(private readonly string $currency, private readonly float $amount)
    {
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }
}
