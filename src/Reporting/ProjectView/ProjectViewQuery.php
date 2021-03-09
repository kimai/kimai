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
    /**
     * @var Customer|null
     */
    private $customer;
    /**
     * @var DateTime
     */
    private $today;
    /**
     * @var User|null
     */
    private $user;
    /**
     * @var bool
     */
    private $includeNoBudget = false;
    /**
     * @var bool
     */
    private $includeNoWork = false;

    public function __construct(DateTime $today, User $user)
    {
        $this->today = $today;
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function isIncludeNoBudget(): bool
    {
        return $this->includeNoBudget;
    }

    public function setIncludeNoBudget(bool $includeNoBudget): void
    {
        $this->includeNoBudget = $includeNoBudget;
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
