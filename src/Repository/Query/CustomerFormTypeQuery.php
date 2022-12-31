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
final class CustomerFormTypeQuery extends BaseFormTypeQuery
{
    private ?Customer $customerToIgnore = null;
    private bool $allowCustomerPreselect = false;

    /**
     * @param Customer|array<Customer>|int|null $customer
     */
    public function __construct(Customer|array|int|null $customer = null)
    {
        if (null !== $customer) {
            if (!\is_array($customer)) {
                $customer = [$customer];
            }
            $this->setCustomers($customer);
        }
    }

    public function isAllowCustomerPreselect(): bool
    {
        return $this->allowCustomerPreselect;
    }

    public function setAllowCustomerPreselect(bool $allowCustomerPreselect): void
    {
        $this->allowCustomerPreselect = $allowCustomerPreselect;
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
