<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Activity;
use App\Entity\Project;

final class ActivityFormTypeQuery extends BaseFormTypeQuery
{
    /**
     * @var Activity|null
     */
    private $activityToIgnore;

    /**
     * @param Activity|int|array|null $activity
     * @param Project|int|array|null $project
     */
    public function __construct($activity = null, $project = null)
    {
        if (null !== $activity) {
            if (!\is_array($activity)) {
                $activity = [$activity];
            }
            $this->setActivities($activity);
        }

        if (null !== $project) {
            if (!\is_array($project)) {
                $project = [$project];
            }
            $this->setProjects($project);
        }
    }

    /**
     * @return Activity|null
     */
    public function getActivityToIgnore(): ?Activity
    {
        return $this->activityToIgnore;
    }

    public function setActivityToIgnore(Activity $activityToIgnore): ActivityFormTypeQuery
    {
        $this->activityToIgnore = $activityToIgnore;

        return $this;
    }

    public function isGlobalsOnly(): bool
    {
        if ($this->hasProjects()) {
            return false;
        }

        if (!$this->hasActivities()) {
            return true;
        }

        foreach ($this->getActivities() as $activity) {
            // this is a potential problem, if only IDs were set
            if ($activity instanceof Activity && !$activity->isGlobal()) {
                return false;
            }
        }

        return true;
    }
}
