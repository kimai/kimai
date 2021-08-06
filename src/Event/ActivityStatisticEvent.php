<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Activity;
use App\Model\ActivityStatistic;

final class ActivityStatisticEvent extends AbstractActivityEvent
{
    private $statistic;
    private $begin;
    private $end;

    public function __construct(Activity $activity, ActivityStatistic $statistic, \DateTime $begin = null, \DateTime $end = null)
    {
        parent::__construct($activity);
        $this->statistic = $statistic;
        $this->begin = $begin;
        $this->end = $end;
    }

    public function getStatistic(): ActivityStatistic
    {
        return $this->statistic;
    }

    public function getBegin(): ?\DateTime
    {
        return $this->begin;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }
}
