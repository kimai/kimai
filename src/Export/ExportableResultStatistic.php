<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\Customer;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class ExportableResultStatistic implements ExportableResultStatisticInterface
{
    /**
     * @param non-negative-int $count
     */
    public function __construct(
        private Customer $customer,
        private int $count,
        private int $duration,
        private float $rate
    )
    {
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getRate(): float
    {
        return $this->rate;
    }
}
