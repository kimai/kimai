<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Activity;

use App\Entity\Activity;
use App\Event\ActivityStatisticEvent;
use App\Model\ActivityStatistic;
use App\Repository\ActivityRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class ActivityStatisticService
{
    private $repository;
    private $dispatcher;

    public function __construct(ActivityRepository $activityRepository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $activityRepository;
        $this->dispatcher = $dispatcher;
    }

    public function getActivityStatistics(Activity $activity): ActivityStatistic
    {
        $statistic = $this->repository->getActivityStatistics($activity);
        $event = new ActivityStatisticEvent($activity, $statistic);
        $this->dispatcher->dispatch($event);

        return $statistic;
    }
}
