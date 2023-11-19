<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting\ActivityView;

use App\Entity\Project;
use App\Entity\User;

final class ActivityViewQuery
{
    private ?Project $project = null;
    private bool $includeNoWork = false;
    private ?bool $budgetType = true;

    public function __construct(private readonly \DateTimeInterface $today, private readonly User $user)
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

    public function isIncludeNoWork(): bool
    {
        return $this->includeNoWork;
    }

    public function setIncludeNoWork(bool $includeNoWork): void
    {
        $this->includeNoWork = $includeNoWork;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
    }

    public function getToday(): \DateTimeInterface
    {
        return $this->today;
    }
}
