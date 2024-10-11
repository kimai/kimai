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
        return array_merge([
            'begin' => null,
            'end' => null,
            'color' => '',
            'type' => 'bar',
            'id' => uniqid('DailyWorkingTimeChart_'),
        ], parent::getOptions($options));
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
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
