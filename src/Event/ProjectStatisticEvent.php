<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Project;
use App\Model\ProjectStatistic;

final class ProjectStatisticEvent extends AbstractProjectEvent
{
    private $statistic;

    public function __construct(Project $project, ProjectStatistic $statistic)
    {
        parent::__construct($project);
        $this->statistic = $statistic;
    }

    public function getStatistic(): ProjectStatistic
    {
        return $this->statistic;
    }
}
