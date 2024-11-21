<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Project;

use App\Entity\Project;
use App\Repository\ActivityRateRepository;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRateRepository;
use App\Repository\Query\ActivityQuery;

final class ProjectDuplicationService
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly ActivityRepository $activityRepository,
        private readonly ProjectRateRepository $projectRateRepository,
        private readonly ActivityRateRepository $activityRateRepository
    ) {
    }

    public function duplicate(Project $project, string $newName): Project
    {
        $newProject = clone $project;
        $newProject->setName($newName);
        $newProject->setNumber($this->projectService->calculateNextProjectNumber());

        foreach ($project->getTeams() as $team) {
            $newProject->addTeam($team);
        }

        foreach ($project->getMetaFields() as $metaField) {
            $newMetaField = clone $metaField;
            $newMetaField->setEntity($newProject);
            $newProject->setMetaField($newMetaField);
        }

        if (null !== $project->getEnd()) {
            $newProject->setStart(clone $project->getEnd());
            $newProject->setEnd(null);
        }

        $this->projectService->saveNewProject($newProject);

        foreach ($this->projectRateRepository->getRatesForProject($project) as $rate) {
            $newRate = clone $rate;
            $newRate->setProject($newProject);
            $this->projectRateRepository->saveRate($newRate);
        }

        $query = new ActivityQuery();
        $query->addProject($project);
        $query->setExcludeGlobals(true);

        $allActivities = $this->activityRepository->getActivitiesForQuery($query);
        foreach ($allActivities as $activity) {
            $newActivity = clone $activity;
            $newActivity->setProject($newProject);
            foreach ($activity->getMetaFields() as $metaField) {
                $newMetaField = clone $metaField;
                $newMetaField->setEntity($newActivity);
                $newActivity->setMetaField($newMetaField);
            }

            $this->activityRepository->saveActivity($newActivity);

            foreach ($this->activityRateRepository->getRatesForActivity($activity) as $rate) {
                $newRate = clone $rate;
                $newRate->setActivity($newActivity);
                $this->activityRateRepository->saveRate($newRate);
            }
        }

        return $newProject;
    }
}
