<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

use DateTime;

final class DateRange
{
    private ?DateTime $begin = null;
    private ?DateTime $end = null;

    public function __construct(private bool $resetTimes = true)
    {
    }

    public function getBegin(): ?DateTime
    {
        return $this->begin;
    }

    public function setBegin(DateTime $begin): DateRange
    {
        $this->begin = $begin;
        if ($this->resetTimes) {
            $this->begin->setTime(0, 0, 0);
        }

        return $this;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setEnd(DateTime $end): DateRange
    {
        $this->end = $end;
        if ($this->resetTimes) {
            $this->end->setTime(23, 59, 59);
        }

        return $this;
    }
}
