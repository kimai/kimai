<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

final class Tax
{
    public function __construct(
        private readonly TaxType $type,
        private readonly string $name = 'VAT',
        private readonly float $rate = 0.0,
    )
    {
    }

    public function getType(): TaxType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRate(): float
    {
        return $this->rate;
    }
}
