<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ProjectDateRange;

use App\Entity\Customer;
use App\Entity\User;

final class ProjectDateRangeQuery
{
    private \DateTime $month;
    private ?User $user;
    private ?Customer $customer = null;
    private bool $includeNoWork = false;
    private ?string $budgetType = null;

    public function __construct(\DateTime $month, User $user)
    {
        $this->month = clone $month;
        $this->user = $user;
    }

    public function isBudgetIndependent(): bool
    {
        return $this->budgetType === null;
    }

    public function isIncludeNoBudget(): bool
    {
        return $this->budgetType === 'none';
    }

    public function isIncludeNoWork(): bool
    {
        return $this->includeNoWork;
    }

    public function setIncludeNoWork(bool $includeNoWork): void
    {
        $this->includeNoWork = $includeNoWork;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getMonth(): ?\DateTime
    {
        return $this->month;
    }

    public function setMonth(?\DateTime $month): void
    {
        $this->month = $month;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function isBudgetTypeMonthly(): bool
    {
        return $this->budgetType === 'month';
    }

    public function getBudgetType(): ?string
    {
        return $this->budgetType;
    }

    public function setBudgetType(?string $budgetType): void
    {
        $this->budgetType = $budgetType;
    }
}
