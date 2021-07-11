<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use DateTime;

class DailyWorkingTimeChart extends SimpleWidget implements UserWidget
{
    public const DEFAULT_CHART = 'bar';

    /**
     * @var TimesheetRepository
     */
    protected $repository;

    public function __construct(TimesheetRepository $repository)
    {
        $this->repository = $repository;
        $this->setId('DailyWorkingTimeChart');
        $this->setTitle('stats.yourWorkingHours');
        $this->setOptions([
            'begin' => null,
            'end' => null,
            'color' => '',
            'type' => self::DEFAULT_CHART,
            'id' => '',
        ]);
    }

    public function setUser(User $user): void
    {
        $this->setOption('user', $user);
    }

    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (!\in_array($options['type'], ['bar', 'line'])) {
            $options['type'] = self::DEFAULT_CHART;
        }

        if (empty($options['id'])) {
            $options['id'] = uniqid('DailyWorkingTimeChart_');
        }

        return $options;
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);

        $user = $options['user'];
        if (null === $user || !($user instanceof User)) {
            throw new \InvalidArgumentException('Widget option "user" must be an instance of ' . User::class);
        }

        $dateTimeFactory = DateTimeFactory::createByUser($user);

        if ($options['begin'] === null) {
            $options['begin'] = $dateTimeFactory->getStartOfWeek();
        }

        if ($options['begin'] instanceof DateTime) {
            $begin = $options['begin'];
        } else {
            $begin = new DateTime($options['begin'], new \DateTimeZone($user->getTimezone()));
        }

        if ($options['end'] === null) {
            $options['end'] = $dateTimeFactory->getEndOfWeek($begin);
        }

        if ($options['end'] instanceof DateTime) {
            $end = $options['end'];
        } else {
            $end = new DateTime($options['end'], new \DateTimeZone($user->getTimezone()));
        }

        $activities = [];
        $statistics = $this->repository->getDailyStats($user, $begin, $end);

        foreach ($statistics as $day) {
            foreach ($day->getDetails() as $entry) {
                /** @var Activity $activity */
                $activity = $entry['activity'];
                /** @var Project $project */
                $project = $entry['project'];

                $id = $project->getId() . '_' . $activity->getId();

                $activities[$id] = [
                    'activity' => $activity,
                    'project' => $project,
                ];
            }
        }

        return [
            'activities' => $activities,
            'data' => $statistics,
        ];
    }
}
