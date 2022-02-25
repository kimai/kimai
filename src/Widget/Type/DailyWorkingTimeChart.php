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
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use App\Widget\WidgetInterface;
use DateTime;

final class DailyWorkingTimeChart extends AbstractWidget
{
    private $repository;

    public function __construct(TimesheetRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_FULL;
    }

    public function getHeight(): int
    {
        return WidgetInterface::HEIGHT_LARGE;
    }

    public function getPermissions(): array
    {
        return ['view_own_timesheet'];
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'begin' => null,
            'end' => null,
            'color' => '',
            'type' => 'bar',
            'id' => uniqid('DailyWorkingTimeChart_'),
        ], parent::getOptions($options));
    }

    public function getData(array $options = [])
    {
        $user = $this->getUser();

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

    public function getTitle(): string
    {
        return 'stats.yourWorkingHours';
    }

    public function getId(): string
    {
        return 'DailyWorkingTimeChart';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-dailyworkingtimechart.html.twig';
    }
}
