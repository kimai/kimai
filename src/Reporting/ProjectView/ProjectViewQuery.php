<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectView;

use App\Entity\Customer;
use App\Entity\User;
use DateTime;

final class ProjectViewQuery
{
    private ?Customer $customer = null;
    private bool $includeNoWork = false;
    private ?bool $budgetType = true;

    public function __construct(private DateTime $today, private User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBudgetType(): ?bool
    {
        return $this->budgetType;
    }

    /**
     * @internal
     */
    public function setBudgetType(?bool $budgetType): void
    {
        $this->budgetType = $budgetType;
    }

    public function isIncludeWithoutBudget(): bool
    {
        return $this->budgetType === false;
    }

    public function isIncludeWithBudget(): bool
    {
        return $this->budgetType === true;
    }

    public function isIncludeNoWork(): bool
    {
        return $this->includeNoWork;
    }

    public function setIncludeNoWork(bool $includeNoWork): void
    {
        $this->includeNoWork = $includeNoWork;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function getToday(): DateTime
    {
        return $this->today;
    }
}
