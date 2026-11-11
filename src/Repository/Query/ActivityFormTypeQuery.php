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
    private ?Activity $activityToIgnore = null;
    private bool $allowActivityPreselect = false;

    /**
     * @param Activity|array<Activity>|int|null $activity
     * @param Project|array<Project>|int|null $project
     */
    public function __construct(Activity|array|int|null $activity = null, Project|array|int|null $project = null)
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
     * Whether the given activities are included, even if the user has no team access to them.
     */
    public function isAllowActivityPreselect(): bool
    {
        return $this->allowActivityPreselect;
    }

    /**
     * Only activate for activities which are taken from an already stored entity (e.g. the activity of an existing timesheet).
     * Never activate it for IDs that were taken from the request, otherwise the team permission check can be bypassed.
     */
    public function setAllowActivityPreselect(bool $allowActivityPreselect): void
    {
        $this->allowActivityPreselect = $allowActivityPreselect;
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
