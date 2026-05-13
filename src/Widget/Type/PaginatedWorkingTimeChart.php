<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;
use DateTime;
use DateTimeInterface;

final class PaginatedWorkingTimeChart extends AbstractWidget
{
    private const PERIODS = ['week', 'month', 'quarter', 'year', 'ytd', 'custom'];
    private const GROUPINGS = ['day', 'week'];
    private const TYPES = ['bar'];

    public function __construct(private TimesheetRepository $repository, private SystemConfiguration $systemConfiguration)
    {
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_FULL;
    }

    public function getHeight(): int
    {
        return WidgetInterface::HEIGHT_MAXIMUM;
    }

    public function getPermissions(): array
    {
        return ['view_own_timesheet'];
    }

    public function getTitle(): string
    {
        return 'stats.yourWorkingHours';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-paginatedworkingtimechart.html.twig';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        $options['type'] = $this->getTypeOption($options);
        $options['period'] = $this->getPeriodOption($options);
        $options['groupBy'] = $this->getGroupingOption($options);

        if (!\array_key_exists('year', $options) || !\array_key_exists('week', $options)) {
            $timezone = date_default_timezone_get();
            if ($this->getUser() !== null) {
                $timezone = $this->getUser()->getTimezone();
            }
            $now = new DateTime('now', new \DateTimeZone($timezone));

            if (!\array_key_exists('year', $options)) {
                $options['year'] = $now->format('o');
            }

            if (!\array_key_exists('week', $options)) {
                $options['week'] = $now->format('W');
            }
        }

        return $options;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @param list<string> $allowed
     */
    private function getStringOption(array $options, string $name, string $default, array $allowed): string
    {
        $value = $options[$name] ?? null;

        if (!\is_string($value) || !\in_array($value, $allowed, true)) {
            return $default;
        }

        return $value;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    private function getTypeOption(array $options): string
    {
        return $this->getStringOption($options, 'type', 'bar', self::TYPES);
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    private function getPeriodOption(array $options): string
    {
        return $this->getStringOption($options, 'period', 'week', self::PERIODS);
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    private function getGroupingOption(array $options): string
    {
        return $this->getStringOption($options, 'groupBy', 'day', self::GROUPINGS);
    }

    private function getLastWeekInYear(int $year): int
    {
        $lastWeekInYear = new DateTime();
        $lastWeekInYear->setISODate($year, 53);

        return $lastWeekInYear->format('W') === '53' ? 53 : 52;
    }

    private function getIntOption(mixed $value, string $exception): int
    {
        if (\is_string($value)) {
            return (int) $value;
        }

        if (!\is_int($value)) {
            throw new WidgetException($exception);
        }

        return $value;
    }

    private function createDateFromOption(mixed $date, DateTimeFactory $dateTimeFactory, bool $endOfDay = false): ?DateTime
    {
        if (!\is_string($date) || trim($date) === '') {
            return null;
        }

        $dateTime = $dateTimeFactory->createDateTimeFromFormat('!Y-m-d', trim($date));

        if (!$dateTime instanceof DateTime) {
            return null;
        }

        if ($endOfDay) {
            return $dateTime->setTime(23, 59, 59);
        }

        return $dateTime->setTime(0, 0, 0);
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    private function getAnchorDate(array $options, DateTimeFactory $dateTimeFactory): DateTime
    {
        $date = $this->createDateFromOption($options['date'] ?? null, $dateTimeFactory);

        if ($date instanceof DateTime) {
            return $date;
        }

        $year = $this->getIntOption($options['year'], 'Invalid year given');
        $week = $this->getIntOption($options['week'], 'Invalid week given');

        return ($dateTimeFactory->createDateTime())->setISODate($year, $week, 1)->setTime(0, 0, 0);
    }

    private function modifyDate(DateTimeInterface $date, string $modifier): DateTime
    {
        $date = DateTime::createFromInterface($date);
        $date->modify($modifier);

        return $date;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array{begin: DateTime, end: DateTime, anchor: DateTime}
     */
    private function getDateRange(array $options, DateTimeFactory $dateTimeFactory): array
    {
        $period = $this->getPeriodOption($options);
        $anchor = $this->getAnchorDate($options, $dateTimeFactory);

        if ($period === 'custom') {
            $begin = $this->createDateFromOption($options['begin'] ?? null, $dateTimeFactory) ?? $dateTimeFactory->getStartOfWeek($anchor);
            $end = $this->createDateFromOption($options['end'] ?? null, $dateTimeFactory, true) ?? $dateTimeFactory->getEndOfWeek($begin);

            if ($end < $begin) {
                $end = (clone $begin)->setTime(23, 59, 59);
            }

            return [
                'begin' => $begin,
                'end' => $end,
                'anchor' => clone $begin,
            ];
        }

        if ($period === 'month') {
            return [
                'begin' => $dateTimeFactory->getStartOfMonth($anchor),
                'end' => $dateTimeFactory->getEndOfMonth($anchor),
                'anchor' => $anchor,
            ];
        }

        if ($period === 'quarter') {
            $quarterStartMonth = (int) (floor(((int) $anchor->format('n') - 1) / 3) * 3) + 1;
            $begin = (clone $anchor)->setDate((int) $anchor->format('Y'), $quarterStartMonth, 1)->setTime(0, 0, 0);

            return [
                'begin' => $begin,
                'end' => $dateTimeFactory->getEndOfMonth($this->modifyDate($begin, '+2 months')),
                'anchor' => $anchor,
            ];
        }

        if ($period === 'year') {
            return [
                'begin' => $dateTimeFactory->createStartOfYear($anchor),
                'end' => $dateTimeFactory->createEndOfYear($anchor),
                'anchor' => $anchor,
            ];
        }

        if ($period === 'ytd') {
            $begin = $dateTimeFactory->createStartOfYear($anchor);
            $end = (clone $anchor)->setTime(23, 59, 59);
            $today = $dateTimeFactory->createDateTime('23:59:59');

            if ($anchor->format('Y') === $today->format('Y') && $end > $today) {
                $end = $today;
            }

            return [
                'begin' => $begin,
                'end' => $end,
                'anchor' => $anchor,
            ];
        }

        $begin = $dateTimeFactory->getStartOfWeek($anchor);

        return [
            'begin' => $begin,
            'end' => $dateTimeFactory->getEndOfWeek($begin),
            'anchor' => $anchor,
        ];
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array<string, string|int>
     */
    private function createRouteOptions(DateTimeInterface $anchor, array $options, ?DateTimeInterface $begin = null, ?DateTimeInterface $end = null): array
    {
        $anchor = DateTime::createFromInterface($anchor);
        $period = $this->getPeriodOption($options);
        $routeOptions = [
            'year' => (int) $anchor->format('o'),
            'week' => (int) $anchor->format('W'),
            'period' => $period,
            'groupBy' => $this->getGroupingOption($options),
            'type' => $this->getTypeOption($options),
            'date' => $anchor->format('Y-m-d'),
        ];

        if ($period === 'custom' && $begin !== null && $end !== null) {
            $routeOptions['begin'] = $begin->format('Y-m-d');
            $routeOptions['end'] = $end->format('Y-m-d');
        }

        return $routeOptions;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array{previous: array<string, string|int>, next: array<string, string|int>}
     */
    private function getNavigationOptions(array $options, DateTimeInterface $anchor, DateTimeInterface $begin, DateTimeInterface $end): array
    {
        $period = $this->getPeriodOption($options);

        if ($period === 'custom') {
            $days = $begin->diff($end)->days;
            if ($days === false) {
                $days = 0;
            }
            $days++;

            $previousBegin = $this->modifyDate($begin, \sprintf('-%d days', $days));
            $previousEnd = $this->modifyDate($end, \sprintf('-%d days', $days));
            $nextBegin = $this->modifyDate($begin, \sprintf('+%d days', $days));
            $nextEnd = $this->modifyDate($end, \sprintf('+%d days', $days));

            return [
                'previous' => $this->createRouteOptions($previousBegin, $options, $previousBegin, $previousEnd),
                'next' => $this->createRouteOptions($nextBegin, $options, $nextBegin, $nextEnd),
            ];
        }

        if ($period === 'ytd') {
            $previous = $this->modifyDate($anchor, '-1 year');
            $next = $this->modifyDate($anchor, '+1 year');
        } else {
            $step = match ($period) {
                'month' => '1 month',
                'quarter' => '3 months',
                'year' => '1 year',
                default => '1 week',
            };

            $previous = $this->modifyDate($begin, '-' . $step);
            $next = $this->modifyDate($begin, '+' . $step);
        }

        return [
            'previous' => $this->createRouteOptions($previous, $options),
            'next' => $this->createRouteOptions($next, $options),
        ];
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return list<array{period: string, label: string, active: bool, routeOptions: array<string, string|int>}>
     */
    private function getPeriodOptions(array $options, DateTimeInterface $anchor, DateTimeInterface $begin, DateTimeInterface $end, DateTimeInterface $today): array
    {
        $labels = [
            'week' => 'stats.workingTimePeriodWeek',
            'month' => 'stats.workingTimePeriodMonth',
            'quarter' => 'stats.workingTimePeriodQuarter',
            'year' => 'stats.workingTimePeriodYear',
            'ytd' => 'stats.workingTimePeriodYtd',
            'custom' => 'stats.workingTimePeriodCustom',
        ];

        $periods = [];

        foreach (self::PERIODS as $period) {
            $periodOptions = $options;
            $periodOptions['period'] = $period;
            $periodAnchor = match ($period) {
                'custom' => $begin,
                'ytd' => $today,
                default => $anchor,
            };

            $periods[] = [
                'period' => $period,
                'label' => $labels[$period],
                'active' => $this->getPeriodOption($options) === $period,
                'routeOptions' => $this->createRouteOptions($periodAnchor, $periodOptions, $begin, $end),
            ];
        }

        return $periods;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return list<array{groupBy: string, label: string, active: bool, routeOptions: array<string, string|int>}>
     */
    private function getGroupingOptions(array $options, DateTimeInterface $anchor, DateTimeInterface $begin, DateTimeInterface $end): array
    {
        $labels = [
            'day' => 'stats.workingTimeGroupDay',
            'week' => 'stats.workingTimeGroupWeek',
        ];

        $groupings = [];

        foreach (self::GROUPINGS as $groupBy) {
            $groupOptions = $options;
            $groupOptions['groupBy'] = $groupBy;

            $groupings[] = [
                'groupBy' => $groupBy,
                'label' => $labels[$groupBy],
                'active' => $this->getGroupingOption($options) === $groupBy,
                'routeOptions' => $this->createRouteOptions($anchor, $groupOptions, $begin, $end),
            ];
        }

        return $groupings;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        $options = $this->getOptions($options);
        $user = $this->getUser();

        $dateTimeFactory = DateTimeFactory::createByUser($user);

        $dateRange = $this->getDateRange($options, $dateTimeFactory);
        $begin = $dateRange['begin'];
        $end = $dateRange['end'];
        $anchor = $dateRange['anchor'];

        $weekBegin = $dateTimeFactory->getStartOfWeek($begin);
        $weekEnd = $dateTimeFactory->getEndOfWeek($weekBegin);

        $year = $this->getPeriodOption($options) === 'week' ? (int) $anchor->format('o') : (int) $begin->format('Y');
        $lastWeekInYear = $this->getLastWeekInYear($year);
        $lastWeekInLastYear = $this->getLastWeekInYear($year - 1);

        $dayBegin = $dateTimeFactory->createDateTime('00:00:00');
        $dayEnd = $dateTimeFactory->createDateTime('23:59:59');

        $monthBegin = $dateTimeFactory->getStartOfMonth($begin);
        $monthEnd = $dateTimeFactory->getEndOfMonth($begin);

        $yearBegin = $dateTimeFactory->createStartOfYear($begin);
        $yearEnd = $dateTimeFactory->createEndOfYear($begin);
        $yearData = $this->repository->getDurationForTimeRange($yearBegin, $yearEnd, $user);

        $financialYearData = null;
        $financialYearBegin = null;

        if (null !== ($financialYear = $this->systemConfiguration->getFinancialYearStart())) {
            $financialYearBegin = $dateTimeFactory->createStartOfFinancialYear($financialYear);
            $financialYearEnd = $dateTimeFactory->createEndOfFinancialYear($financialYearBegin);
            $financialYearData = $this->repository->getDurationForTimeRange($financialYearBegin, $financialYearEnd, $user);
        }

        $navigation = $this->getNavigationOptions($options, $anchor, $begin, $end);

        return [
            'begin' => clone $begin,
            'end' => clone $end,
            'date' => clone $anchor,
            'dateYear' => $year,
            'thisMonth' => clone $monthBegin,
            'lastWeekInYear' => $lastWeekInYear,
            'lastWeekInLastYear' => $lastWeekInLastYear,
            'period' => $this->getPeriodOption($options),
            'groupBy' => $this->getGroupingOption($options),
            'type' => $this->getTypeOption($options),
            'previous' => $navigation['previous'],
            'next' => $navigation['next'],
            'periods' => $this->getPeriodOptions($options, $anchor, $begin, $end, $dayBegin),
            'groupings' => $this->getGroupingOptions($options, $anchor, $begin, $end),
            'current' => $this->repository->getDurationForTimeRange($begin, $end, $user),
            'day' => $this->repository->getDurationForTimeRange($dayBegin, $dayEnd, $user),
            'week' => $this->repository->getDurationForTimeRange($weekBegin, $weekEnd, $user),
            'month' => $this->repository->getDurationForTimeRange($monthBegin, $monthEnd, $user),
            'year' => $yearData,
            'financial' => $financialYearData,
            'financialBegin' => $financialYearBegin,
        ];
    }

    public function getId(): string
    {
        return 'PaginatedWorkingTimeChart';
    }
}
