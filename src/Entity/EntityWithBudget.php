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
    public function getBudget(): float;

    public function hasBudget(): bool;

    public function getTimeBudget(): int;

    public function hasTimeBudget(): bool;

    public function isMonthlyBudget(): bool;
}
