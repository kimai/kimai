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

final class ActivityFormTypeQuery
{
    /**
     * @var Activity|int|null
     */
    private $activity;
    /**
     * @var Project|int|null
     */
    private $project;
    /**
     * @var Activity|null
     */
    private $activityToIgnore;

    /**
     * @param Activity|int|null $activity
     * @param Project|int|null $project
     */
    public function __construct($activity = null, $project = null)
    {
        $this->activity = $activity;
        $this->project = $project;
    }

    /**
     * @return Activity|int|null
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param Activity|int|null $activity
     * @return ActivityFormTypeQuery
     */
    public function setActivity($activity): ActivityFormTypeQuery
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @return Project|int|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project|int|null $project
     * @return ActivityFormTypeQuery
     */
    public function setProject($project): ActivityFormTypeQuery
    {
        $this->project = $project;

        return $this;
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
        return
            (
                null === $this->activity ||
                ($this->activity instanceof Activity && null === $this->activity->getProject())
            )
            &&
            null === $this->project;
    }
}
