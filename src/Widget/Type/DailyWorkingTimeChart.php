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
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\Statistic\Day;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use App\Widget\WidgetInterface;
use DateTime;
use DateTimeInterface;

/**
 * This is rendered inside the PaginatedWorkingTimeChart.
 */
final class DailyWorkingTimeChart extends AbstractWidget
{
    public function __construct(private readonly TimesheetRepository $repository)
    {
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

    public function isInternal(): bool
    {
        return true;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        $options = array_merge([
            'begin' => null,
            'end' => null,
            'color' => '',
            'type' => 'bar',
            'groupBy' => 'day',
            'id' => uniqid('DailyWorkingTimeChart_'),
        ], parent::getOptions($options));

        if (($options['type'] ?? null) !== 'bar') {
            $options['type'] = 'bar';
        }

        if (!\in_array($options['groupBy'] ?? null, ['day', 'week'], true)) {
            $options['groupBy'] = 'day';
        }

        return $options;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        $options = $this->getOptions($options);
        $user = $this->getUser();
        $dateTimeFactory = DateTimeFactory::createByUser($user);

        $begin = $options['begin'];
        if (!($begin instanceof \DateTimeInterface)) {
            if (\is_string($begin)) {
                $begin = new \DateTimeImmutable($begin, new \DateTimeZone($user->getTimezone()));
            } else {
                $begin = $dateTimeFactory->getStartOfWeek();
            }
        }

        $end = $options['end'];
        if (!($end instanceof \DateTimeInterface)) {
            if (\is_string($end)) {
                $end = new \DateTimeImmutable($end, new \DateTimeZone($user->getTimezone()));
            } else {
                $end = $dateTimeFactory->getEndOfWeek($begin);
            }
        }

        $activities = [];
        $statistics = $this->getPreparedData($user, $begin, $end);

        if (($options['groupBy'] ?? 'day') === 'week') {
            $statistics = $this->getWeeklyData($statistics, $dateTimeFactory);
        }

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

        $activities = $this->sortActivitiesByProject($activities);

        return [
            'activities' => $activities,
            'data' => $statistics,
        ];
    }

    /**
     * @param array<string, array{activity: Activity, project: Project}> $activities
     * @return array<string, array{activity: Activity, project: Project}>
     */
    private function sortActivitiesByProject(array $activities): array
    {
        uasort($activities, static function (array $left, array $right): int {
            $leftProject = $left['project'];
            $rightProject = $right['project'];
            $leftActivity = $left['activity'];
            $rightActivity = $right['activity'];
            $leftCustomer = $leftProject->getCustomer();
            $rightCustomer = $rightProject->getCustomer();

            return [
                $leftCustomer?->getName() ?? '',
                $leftProject->getName() ?? '',
                $leftActivity->getName() ?? '',
                $leftCustomer?->getId() ?? 0,
                $leftProject->getId() ?? 0,
                $leftActivity->getId() ?? 0,
            ] <=> [
                $rightCustomer?->getName() ?? '',
                $rightProject->getName() ?? '',
                $rightActivity->getName() ?? '',
                $rightCustomer?->getId() ?? 0,
                $rightProject->getId() ?? 0,
                $rightActivity->getId() ?? 0,
            ];
        });

        return $activities;
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

    /**
     * @return list<array{'duration': int, 'billable': int, 'month': numeric-string, 'year': numeric-string, 'day': numeric-string, 'details': array<int|string, array<mixed>>}>
     */
    private function getDailyData(DateTimeInterface $begin, DateTimeInterface $end, User $user): array
    {
        $qb = $this->repository->createQueryBuilder('t');

        $qb->select('t, p, a, c')
            ->andWhere($qb->expr()->between('t.date', ':begin', ':end'))
            ->andWhere($qb->expr()->eq('t.user', ':user'))
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->setParameter('begin', $begin->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->setParameter('user', $user)
            ->leftJoin('t.activity', 'a')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.customer', 'c')
        ;

        $timesheets = $qb->getQuery()->getResult();

        $results = [];
        /** @var Timesheet $result */
        foreach ($timesheets as $result) {
            /** @var DateTime $beginTmp */
            $beginTmp = $result->getBegin();

            $dateKey = $beginTmp->format('Ymd');

            if (!isset($results[$dateKey])) {
                $results[$dateKey] = [
                    'duration' => 0,
                    'billable' => 0, // duration
                    'month' => $beginTmp->format('n'),
                    'year' => $beginTmp->format('Y'),
                    'day' => $beginTmp->format('j'),
                    'details' => []
                ];
            }
            $duration = $result->getDuration() ?? 0;

            $results[$dateKey]['duration'] += $duration;
            if ($result->isBillable()) {
                $results[$dateKey]['billable'] += $duration;
            }
            $detailsId =
                $result->getProject()->getCustomer()->getId()
                . '_' . ($result->getProject()?->getId() ?? '')
                . '_' . ($result->getActivity()?->getId() ?? '')
            ;

            if (!isset($results[$dateKey]['details'][$detailsId])) {
                $results[$dateKey]['details'][$detailsId] = [
                    'project' => $result->getProject(),
                    'activity' => $result->getActivity(),
                    'duration' => 0,
                    'billable' => 0, // duration
                ];
            }

            $results[$dateKey]['details'][$detailsId]['duration'] += $duration;
            if ($result->isBillable()) {
                $results[$dateKey]['details'][$detailsId]['billable'] += $duration;
            }
        }

        ksort($results);

        foreach ($results as $key => $value) {
            $results[$key]['details'] = array_values($value['details']);
        }

        return array_values($results);
    }

    /**
     * @param Day[] $days
     * @return Day[]
     */
    private function getWeeklyData(array $days, DateTimeFactory $dateTimeFactory): array
    {
        /** @var array<string, Day> $weeks */
        $weeks = [];
        /** @var array<string, array<string, array{project: Project, activity: Activity, duration: int, billable: int}>> $weekDetails */
        $weekDetails = [];

        foreach ($days as $day) {
            $weekBegin = $dateTimeFactory->getStartOfWeek($day->getDay());
            $weekKey = $weekBegin->format('Ymd');

            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = new Day($weekBegin, 0, 0.00);
                $weekDetails[$weekKey] = [];
            }

            $weeks[$weekKey]->setTotalDuration($weeks[$weekKey]->getTotalDuration() + $day->getTotalDuration());
            $weeks[$weekKey]->setTotalDurationBillable($weeks[$weekKey]->getTotalDurationBillable() + $day->getTotalDurationBillable());

            foreach ($day->getDetails() as $entry) {
                if (!isset($entry['project'], $entry['activity'])) {
                    continue;
                }

                $project = $entry['project'];
                $activity = $entry['activity'];

                if (!$project instanceof Project || !$activity instanceof Activity) {
                    continue;
                }

                $customer = $project->getCustomer();
                if ($customer === null) {
                    continue;
                }

                $detailsId =
                    $customer->getId()
                    . '_' . ($project->getId() ?? '')
                    . '_' . ($activity->getId() ?? '')
                ;

                if (!isset($weekDetails[$weekKey][$detailsId])) {
                    $weekDetails[$weekKey][$detailsId] = [
                        'project' => $project,
                        'activity' => $activity,
                        'duration' => 0,
                        'billable' => 0,
                    ];
                }

                $weekDetails[$weekKey][$detailsId]['duration'] += (int) ($entry['duration'] ?? 0);
                $weekDetails[$weekKey][$detailsId]['billable'] += (int) ($entry['billable'] ?? 0);
            }
        }

        foreach ($weeks as $weekKey => $week) {
            $week->setDetails(array_values($weekDetails[$weekKey]));
        }

        return array_values($weeks);
    }

    /**
     * @return Day[]
     * @throws \Exception
     */
    private function getPreparedData(User $user, DateTimeInterface $begin, DateTimeInterface $end): array
    {
        /** @var Day[] $days */
        $days = [];

        // prefill the array
        $tmp = DateTime::createFromInterface($end);
        $until = (int) $begin->format('Ymd');
        while ((int) $tmp->format('Ymd') >= $until) {
            $last = clone $tmp;
            $days[$last->format('Ymd')] = new Day($last, 0, 0.00);
            $tmp->modify('-1 day');
        }

        // TODO replace with TimesheetStatisticService::getDailyStatistics()
        $results = $this->getDailyData($begin, $end, $user);

        foreach ($results as $statRow) {
            $dateTime = DateTime::createFromInterface($begin);
            $dateTime->setDate((int) $statRow['year'], (int) $statRow['month'], (int) $statRow['day']);
            $dateTime->setTime(0, 0, 0);
            $day = new Day($dateTime, (int) $statRow['duration'], 0.00); // rate is not used in frontend
            $day->setTotalDurationBillable($statRow['billable']);
            $day->setDetails($statRow['details']);
            $dateKey = $dateTime->format('Ymd');
            // make sure entries from other timezones are filtered
            if (!\array_key_exists($dateKey, $days)) {
                continue;
            }
            $days[$dateKey] = $day;
        }

        ksort($days);

        return array_values($days);
    }
}
