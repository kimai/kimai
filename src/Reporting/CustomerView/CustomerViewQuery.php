<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\CustomerView;

use App\Entity\User;
use DateTime;

final class CustomerViewQuery
{
    private ?bool $budgetType = true;

    public function __construct(private DateTime $today, private User $user)
    {
    }

    public function getUser(): ?User
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

    public function getToday(): DateTime
    {
        return $this->today;
    }
}
