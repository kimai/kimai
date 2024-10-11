<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Form\Model\DateRange;

/**
 * @internal
 */
interface DateRangeInterface
{
    public function getBegin(): ?\DateTime;

    public function setBegin(\DateTimeInterface $begin): void;

    public function getEnd(): ?\DateTime;

    public function setEnd(\DateTimeInterface $end): void;

    public function getDateRange(): ?DateRange;

    public function setDateRange(DateRange $dateRange): void;
}
