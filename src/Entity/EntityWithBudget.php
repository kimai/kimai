<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

/**
 * @internal
 */
interface EntityWithBudget
{
    public function setBudget(float $budget): void;

    public function getBudget(): float;

    public function hasBudget(): bool;

    public function setTimeBudget(int $seconds): void;

    public function getTimeBudget(): int;

    public function hasTimeBudget(): bool;

    public function isMonthlyBudget(): bool;

    public function getBudgetType(): ?string;

    public function setBudgetType(?string $budgetType = null): void;
}
