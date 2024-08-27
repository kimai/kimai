<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Customer;

trait CustomerTrait
{
    /**
     * @var array<Customer>
     */
    private array $customers = [];

    public function addCustomer(Customer $customer): self
    {
        $this->customers[] = $customer;

        return $this;
    }

    /**
     * @param array<Customer> $customers
     * @return $this
     */
    public function setCustomers(array $customers): self
    {
        $this->customers = $customers;

        return $this;
    }

    /**
     * @return array<Customer>
     */
    public function getCustomers(): array
    {
        return $this->customers;
    }

    /**
     * @return array<int>
     */
    public function getCustomerIds(): array
    {
        return array_filter(array_values(array_unique(array_map(function (Customer $customer) {
            return $customer->getId();
        }, $this->customers))), function ($id) {
            return $id !== null;
        });
    }

    public function hasCustomers(): bool
    {
        return !empty($this->customers);
    }
}
