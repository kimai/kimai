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
final class CustomerFormTypeQuery
{
    /**
     * @var Customer|int|null
     */
    private $customer;
    /**
     * @var Customer|null
     */
    private $customerToIgnore;

    /**
     * @param Customer|int|null $customer
     */
    public function __construct($customer = null)
    {
        $this->customer = $customer;
    }

    /**
     * @return Customer|int|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer|int|null $customer
     * @return $this
     */
    public function setCustomer($customer): CustomerFormTypeQuery
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Customer|null
     */
    public function getCustomerToIgnore(): ?Customer
    {
        return $this->customerToIgnore;
    }

    public function setCustomerToIgnore(Customer $customerToIgnore): CustomerFormTypeQuery
    {
        $this->customerToIgnore = $customerToIgnore;

        return $this;
    }
}
