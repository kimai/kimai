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
    /**
     * @var DateTime
     */
    private $begin;
    /**
     * @var DateTime
     */
    private $end;

    public function getBegin(): ?DateTime
    {
        return $this->begin;
    }

    public function setBegin(DateTime $begin): DateRange
    {
        $this->begin = $begin;

        return $this;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setEnd(DateTime $end): DateRange
    {
        $this->end = $end;

        return $this;
    }
}
