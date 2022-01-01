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
    private $statistic;
    private $begin;
    private $end;

    public function __construct(Customer $customer, CustomerStatistic $statistic, \DateTime $begin = null, \DateTime $end = null)
    {
        parent::__construct($customer);
        $this->statistic = $statistic;
        $this->begin = $begin;
        $this->end = $end;
    }

    public function getStatistic(): CustomerStatistic
    {
        return $this->statistic;
    }

    public function getBegin(): ?\DateTime
    {
        return $this->begin;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }
}
