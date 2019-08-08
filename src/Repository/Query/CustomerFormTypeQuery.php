<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Customer;
use App\Entity\Team;
use App\Entity\User;

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
     * @var User
     */
    private $user;
    /**
     * @var array<Team>
     */
    private $teams = [];

    /**
     * @param Customer|int|null $customer
     */
    public function __construct($customer = null)
    {
        $this->customer = $customer;
    }

    public function addTeam(Team $team): CustomerFormTypeQuery
    {
        $this->teams[$team->getId()] = $team;

        return $this;
    }

    /**
     * @return Team[]
     */
    public function getTeams(): array
    {
        return array_values($this->teams);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): CustomerFormTypeQuery
    {
        $this->user = $user;

        return $this;
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
