<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Form\Model\DateRange;

trait DateRangeTrait
{
    /**
     * @var DateRange
     */
    protected $dateRange;

    public function getBegin(): ?\DateTime
    {
        if (null === $this->dateRange) {
            return null;
        }

        return $this->dateRange->getBegin();
    }

    public function setBegin(\DateTime $begin): void
    {
        $this->dateRange->setBegin($begin);
    }

    public function getEnd(): ?\DateTime
    {
        if (null === $this->dateRange) {
            return null;
        }

        return $this->dateRange->getEnd();
    }

    public function setEnd(\DateTime $end): void
    {
        $this->dateRange->setEnd($end);
    }

    public function getDateRange(): ?DateRange
    {
        return $this->dateRange;
    }

    public function setDateRange(DateRange $dateRange): void
    {
        $this->dateRange = $dateRange;
    }
}
