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

    public function __construct(Customer $customer, CustomerStatistic $statistic)
    {
        parent::__construct($customer);
        $this->statistic = $statistic;
    }

    public function getStatistic(): CustomerStatistic
    {
        return $this->statistic;
    }
}
