<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Customer;

/**
 * Can be used for advanced queries with the: CustomerRepository
 */
class CustomerQuery extends VisibilityQuery
{
    /**
     * @var array
     */
    protected $ignored = [];

    /**
     * @param Customer|int $customer
     * @return $this
     */
    public function addIgnoredEntity($customer)
    {
        $this->ignored[] = $customer;

        return $this;
    }

    /**
     * @return array
     */
    public function getIgnoredEntities()
    {
        return $this->ignored;
    }
}
