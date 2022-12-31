<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait BudgetTrait
{
    /**
     * The total monetary budget, will be zero if not configured.
     */
    #[ORM\Column(name: 'budget', type: 'float', nullable: false)]
    #[Assert\Range(min: 0.00, max: 900000000000.00)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Activity_Entity', 'Project_Entity', 'Customer_Entity'])]
    #[Exporter\Expose(label: 'budget', type: 'float')]
    private float $budget = 0.00;
    /**
     * The time budget in seconds, will be zero if not configured.
     */
    #[ORM\Column(name: 'time_budget', type: 'integer', nullable: false)]
    #[Assert\Range(min: 0, max: 2145600000)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Activity_Entity', 'Project_Entity', 'Customer_Entity'])]
    #[Exporter\Expose(label: 'timeBudget', type: 'duration')]
    private int $timeBudget = 0;
    /**
     * The type of budget:
     *  - null      = default / full time
     *  - month     = monthly budget
     */
    #[ORM\Column(name: 'budget_type', type: 'string', length: 10, nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Activity_Entity', 'Project_Entity', 'Customer_Entity'])]
    #[Exporter\Expose(label: 'budgetType')]
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
