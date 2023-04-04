<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

use App\Model\Year as BaseYear;

final class YearSummary extends BaseYear
{
    private int $value = 0;

    public function __construct(\DateTimeInterface $month, private string $title)
    {
        parent::__construct($month);
    }

    protected function createMonth(\DateTimeInterface $month): MonthSummary
    {
        return new MonthSummary($month);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }
}
