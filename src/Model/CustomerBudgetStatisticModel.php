<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Customer;

/**
 * Object used to unify the access to budget data in charts.
 *
 * @method Customer getEntity()
 */
class CustomerBudgetStatisticModel extends BudgetStatisticModel
{
    public function __construct(Customer $customer)
    {
        parent::__construct($customer);
    }

    public function getCustomer(): Customer
    {
        return $this->getEntity();
    }
}
