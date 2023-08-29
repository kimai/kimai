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
    public function __construct(Activity $activity, private ActivityStatistic $statistic, private ?\DateTime $begin = null, private ?\DateTime $end = null)
    {
        parent::__construct($activity);
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
