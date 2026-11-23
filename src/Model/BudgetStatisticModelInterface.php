<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

interface BudgetStatisticModelInterface
{
    public function isMonthlyBudget(): bool;

    public function hasTimeBudget(): bool;

    public function getTimeBudget(): int;

    public function getTimeBudgetSpent(): int;

    public function hasBudget(): bool;

    public function getBudget(): float;

    public function getBudgetSpent(): float;
}
