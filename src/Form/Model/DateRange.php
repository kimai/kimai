<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

use DateTime;

class DateRange
{
    /**
     * @var DateTime
     */
    protected $begin;
    /**
     * @var DateTime
     */
    protected $end;

    /**
     * @return DateTime
     */
    public function getBegin(): ?DateTime
    {
        return $this->begin;
    }

    /**
     * @param DateTime $begin
     * @return DateRange
     */
    public function setBegin(DateTime $begin)
    {
        $this->begin = $begin;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    /**
     * @param DateTime $end
     * @return DateRange
     */
    public function setEnd(DateTime $end)
    {
        $this->end = $end;

        return $this;
    }
}
