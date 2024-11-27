<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Activity;

use App\Configuration\SystemConfiguration;
use App\Entity\Activity;
use App\Entity\Project;
use App\Event\ActivityCreateEvent;
use App\Event\ActivityCreatePostEvent;
use App\Event\ActivityCreatePreEvent;
use App\Event\ActivityDeleteEvent;
use App\Event\ActivityMetaDefinitionEvent;
use App\Event\ActivityUpdatePostEvent;
use App\Event\ActivityUpdatePreEvent;
use App\Repository\ActivityRepository;
use App\Utils\NumberGenerator;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @final
 */
class ActivityService
{
    public function __construct(
        private readonly ActivityRepository $repository,
        private readonly SystemConfiguration $configuration,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ValidatorInterface $validator
    )
    {
    }

    public function createNewActivity(?Project $project = null): Activity
    {
        $activity = new Activity();
        $activity->setNumber($this->calculateNextActivityNumber());

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

    public function deleteActivity(Activity $activity): void
    {
        $this->dispatcher->dispatch(new ActivityDeleteEvent($activity));
        $this->repository->deleteActivity($activity);
    }

    /**
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

    public function findActivityByNumber(string $number): ?Activity
    {
        return $this->repository->findOneBy(['number' => $number]);
    }

    private function calculateNextActivityNumber(): ?string
    {
        $format = $this->configuration->find('activity.number_format');
        if (empty($format) || !\is_string($format)) {
            return null;
        }

        // we cannot use max(number) because a varchar column returns unexpected results
        $start = $this->repository->countActivity();
        $i = 0;

        do {
            $start++;

            $numberGenerator = new NumberGenerator($format, function (string $originalFormat, string $format, int $increaseBy) use ($start): string|int {
                return match ($format) {
                    'ac' => $start + $increaseBy,
                    default => $originalFormat,
                };
            });

            $number = $numberGenerator->getNumber();
            $activity = $this->findActivityByNumber($number);
        } while ($activity !== null && $i++ < 100);

        if ($activity !== null) {
            return null;
        }

        return $number;
    }
}
