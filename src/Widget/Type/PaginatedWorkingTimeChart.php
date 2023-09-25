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

final class PaginatedWorkingTimeChart extends AbstractWidget
{
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
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (!\array_key_exists('type', $options) || !\in_array($options['type'], ['bar', 'line'])) {
            $options['type'] = 'bar';
        }

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

    private function getLastWeekInYear($year): int
    {
        $lastWeekInYear = new DateTime();
        $lastWeekInYear->setISODate($year, 53);

        return $lastWeekInYear->format('W') === '53' ? 53 : 52;
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        $user = $this->getUser();

        $dateTimeFactory = DateTimeFactory::createByUser($user);

        $year = $options['year'];
        if (\is_string($year)) {
            $year = (int) $year;
        } elseif (!\is_int($year)) {
            throw new WidgetException('Invalid year given');
        }

        $week = $options['week'];
        if (\is_string($week)) {
            $week = (int) $week;
        } elseif (!\is_int($week)) {
            throw new WidgetException('Invalid week given');
        }

        $weekBegin = ($dateTimeFactory->createDateTime())->setISODate($year, $week, 1)->setTime(0, 0, 0);

        $weekBegin = $dateTimeFactory->getStartOfWeek($weekBegin);
        $weekEnd = $dateTimeFactory->getEndOfWeek($weekBegin);

        $lastWeekInYear = $this->getLastWeekInYear($year);
        $lastWeekInLastYear = $this->getLastWeekInYear($year - 1);

        $thisMonth = clone $weekBegin;
        if ($week === 1) {
            $thisMonth = ($dateTimeFactory->createDateTime())->setISODate($year, $week, 1)->setTime(0, 0, 0);
        }

        $dayBegin = $dateTimeFactory->createDateTime('00:00:00');
        $dayEnd = $dateTimeFactory->createDateTime('23:59:59');

        $monthBegin = (clone $weekBegin)->setDate((int) $weekBegin->format('Y'), (int) $weekBegin->format('n'), 1)->setTime(0, 0, 0);
        $monthEnd = (clone $weekBegin)->setDate((int) $weekBegin->format('Y'), (int) $weekBegin->format('n'), (int) $weekBegin->format('t'))->setTime(23, 59, 59);

        $yearBegin = $dateTimeFactory->createDateTime(sprintf('01 january %s 00:00:00', $year));
        $yearEnd = $dateTimeFactory->createDateTime(sprintf('31 december %s 23:59:59', $year));
        $yearData = $this->repository->getDurationForTimeRange($yearBegin, $yearEnd, $user);

        $financialYearData = null;
        $financialYearBegin = null;

        if (null !== ($financialYear = $this->systemConfiguration->getFinancialYearStart())) {
            $financialYearBegin = $dateTimeFactory->createStartOfFinancialYear($financialYear);
            $financialYearEnd = $dateTimeFactory->createEndOfFinancialYear($financialYearBegin);
            $financialYearData = $this->repository->getDurationForTimeRange($financialYearBegin, $financialYearEnd, $user);
        }

        return [
            'begin' => clone $weekBegin,
            'end' => clone $weekEnd,
            'thisMonth' => $thisMonth,
            'lastWeekInYear' => $lastWeekInYear,
            'lastWeekInLastYear' => $lastWeekInLastYear,
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
