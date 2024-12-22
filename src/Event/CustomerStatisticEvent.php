<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Customer;
use App\Model\CustomerStatistic;

final class CustomerStatisticEvent extends AbstractCustomerEvent
{
    public function __construct(Customer $customer, private readonly CustomerStatistic $statistic, private readonly ?\DateTimeInterface $begin = null, private readonly ?\DateTimeInterface $end = null)
    {
        parent::__construct($customer);
    }

    public function getStatistic(): CustomerStatistic
    {
        return $this->statistic;
    }

    public function getBegin(): ?\DateTimeInterface
    {
        return $this->begin;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }
}
