<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait BudgetTrait
{
    /**
     * The total monetary budget (default: 0).
     *
     * Optional. Only included if the current user may see the monetary budget of this record (`budget_<object>` and `budget_team<lead>_<object>` permission).
     */
    #[ORM\Column(name: 'budget', type: Types::FLOAT, nullable: false)]
    #[Assert\Range(min: 0.00, max: 900000000000.00)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Budget_Money'])]
    #[Exporter\Expose(label: 'budget', type: 'float', permissions: ['budget'])]
    private float $budget = 0.00;
    /**
     * The time budget in seconds (default: 0).
     *
     * Optional. Only included if the current user may see the time budget of this record (`time_<object>` and `time_team<lead>_<object>` permission).
     */
    #[ORM\Column(name: 'time_budget', type: Types::INTEGER, nullable: false)]
    #[Assert\Range(min: 0, max: 2145600000)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Budget_Time'])]
    #[Exporter\Expose(label: 'timeBudget', type: 'duration', permissions: ['time'])]
    private int $timeBudget = 0;
    /**
     * The type of budget:
     *  - null = default / full time
     *  - month = monthly budget
     *
     * Optional. Only included if the current user may see the monetary or the time budget of this record.
     */
    #[ORM\Column(name: 'budget_type', type: Types::STRING, length: 10, nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Budget_Money', 'Budget_Time'])]
    #[Exporter\Expose(label: 'budgetType', permissions: ['time', 'budget'])]
    private ?string $budgetType = null;

    public function setBudget(float $budget): void
    {
        $this->budget = $budget;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function hasBudget(): bool
    {
        return $this->budget > 0.00;
    }

    public function setTimeBudget(int $seconds): void
    {
        $this->timeBudget = $seconds;
    }

    public function getTimeBudget(): int
    {
        return $this->timeBudget;
    }

    public function hasTimeBudget(): bool
    {
        return $this->timeBudget > 0;
    }

    public function setBudgetType(?string $budgetType = null): void
    {
        if ($budgetType !== null && !\in_array($budgetType, ['month'])) {
            throw new \InvalidArgumentException('Unknown budget type: ' . $budgetType);
        }
        $this->budgetType = $budgetType;
    }

    public function setIsMonthlyBudget(): void
    {
        $this->setBudgetType('month');
    }

    public function getBudgetType(): ?string
    {
        return $this->budgetType;
    }

    public function isMonthlyBudget(): bool
    {
        return $this->hasBudgets() && $this->budgetType === 'month';
    }

    public function hasBudgets(): bool
    {
        return ($this->hasTimeBudget() || $this->hasBudget());
    }
}
