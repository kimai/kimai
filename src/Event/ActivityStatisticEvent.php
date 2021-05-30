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

    public function __construct(Activity $activity, ActivityStatistic $statistic)
    {
        parent::__construct($activity);
        $this->statistic = $statistic;
    }

    public function getStatistic(): ActivityStatistic
    {
        return $this->statistic;
    }
}
