<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Activity;

use App\Entity\Activity;
use App\Entity\Project;
use App\Event\ActivityCreateEvent;
use App\Event\ActivityCreatePostEvent;
use App\Event\ActivityCreatePreEvent;
use App\Event\ActivityMetaDefinitionEvent;
use App\Event\ActivityUpdatePostEvent;
use App\Event\ActivityUpdatePreEvent;
use App\Repository\ActivityRepository;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @final
 */
class ActivityService
{
    public function __construct(private ActivityRepository $repository, private EventDispatcherInterface $dispatcher, private ValidatorInterface $validator)
    {
    }

    public function createNewActivity(?Project $project = null): Activity
    {
        $activity = new Activity();

        if ($project !== null) {
            $activity->setProject($project);
        }

        $this->dispatcher->dispatch(new ActivityMetaDefinitionEvent($activity));
        $this->dispatcher->dispatch(new ActivityCreateEvent($activity));

        return $activity;
    }

    public function saveNewActivity(Activity $activity): Activity
    {
        if (null !== $activity->getId()) {
            throw new InvalidArgumentException('Cannot create activity, already persisted');
        }

        $this->validateActivity($activity);

        $this->dispatcher->dispatch(new ActivityCreatePreEvent($activity));
        $this->repository->saveActivity($activity);
        $this->dispatcher->dispatch(new ActivityCreatePostEvent($activity));

        return $activity;
    }

    /**
     * @param Activity $activity
     * @param string[] $groups
     * @throws ValidationFailedException
     */
    private function validateActivity(Activity $activity, array $groups = []): void
    {
        $errors = $this->validator->validate($activity, null, $groups);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors, 'Validation Failed');
        }
    }

    public function updateActivity(Activity $activity): Activity
    {
        $this->validateActivity($activity);

        $this->dispatcher->dispatch(new ActivityUpdatePreEvent($activity));
        $this->repository->saveActivity($activity);
        $this->dispatcher->dispatch(new ActivityUpdatePostEvent($activity));

        return $activity;
    }

    public function findActivityByName(string $name, ?Project $project = null): ?Activity
    {
        return $this->repository->findOneBy(['project' => $project?->getId(), 'name' => $name]);
    }
}
