<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use DateTime;

final class PaginatedWorkingTimeChart extends SimpleWidget implements UserWidget
{
    private $repository;
    private $systemConfiguration;

    public function __construct(TimesheetRepository $repository, SystemConfiguration $systemConfiguration)
    {
        $this->repository = $repository;
        $this->systemConfiguration = $systemConfiguration;
        $this->setTitle('stats.yourWorkingHours');
    }

    public function setUser(User $user): void
    {
        $this->setOption('user', $user);
        $now = new DateTime('now', new \DateTimeZone($user->getTimezone()));
        $this->setOptions([
            'year' => $now->format('o'),
            'week' => $now->format('W'),
        ]);
    }

    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (!\array_key_exists('type', $options) || !\in_array($options['type'], ['bar', 'line'])) {
            $options['type'] = 'bar';
        }

        if (!\array_key_exists('year', $options)) {
            $options['year'] = (new DateTime('now'))->format('o');
            $options['week'] = (new DateTime('now'))->format('W');
        }

        return $options;
    }

    private function getLastWeekInYear($year): int
    {
        $lastWeekInYear = new DateTime();
        $lastWeekInYear->setISODate($year, 53);

        return $lastWeekInYear->format('W') === '53' ? 53 : 52;
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);

        $user = $options['user'];
        if (null === $user || !($user instanceof User)) {
            throw new \InvalidArgumentException('Widget option "user" must be an instance of ' . User::class);
        }

        $dateTimeFactory = DateTimeFactory::createByUser($user);

        $year = $options['year'];
        $week = $options['week'];

        $weekBegin = ($dateTimeFactory->createDateTime())->setISODate($year, $week, 1)->setTime(0, 0, 0);

        $weekBegin = $dateTimeFactory->getStartOfWeek($weekBegin);
        $weekEnd = $dateTimeFactory->getEndOfWeek($weekBegin);

        $lastWeekInYear = $this->getLastWeekInYear($year);
        $lastWeekInLastYear = $this->getLastWeekInYear($year - 1);

        $thisMonth = clone $weekBegin;
        if ((int) $week === 1) {
            $thisMonth = ($dateTimeFactory->createDateTime())->setISODate($year, $week, 1)->setTime(0, 0, 0);
        }

        $dayBegin = $dateTimeFactory->createDateTime('00:00:00');
        $dayEnd = $dateTimeFactory->createDateTime('23:59:59');

        $monthBegin = (clone $weekBegin)->setDate((int) $weekBegin->format('Y'), (int) $weekBegin->format('n'), 1)->setTime(0, 0, 0);
        $monthEnd = (clone $weekBegin)->setDate((int) $weekBegin->format('Y'), (int) $weekBegin->format('n'), (int) $weekBegin->format('t'))->setTime(23, 59, 59);

        $yearBegin = $dateTimeFactory->createDateTime(sprintf('01 january %s 00:00:00', $year));
        $yearEnd = $dateTimeFactory->createDateTime(sprintf('31 december %s 23:59:59', $year));
        $yearData = $this->repository->getStatistic('duration', $yearBegin, $yearEnd, $user);

        $financialYearData = null;
        $financialYearBegin = null;

        if (null !== ($financialYear = $this->systemConfiguration->getFinancialYearStart())) {
            $financialYearBegin = $dateTimeFactory->createStartOfFinancialYear($financialYear);
            $financialYearEnd = $dateTimeFactory->createEndOfFinancialYear($financialYearBegin);
            $financialYearData = $this->repository->getStatistic('duration', $financialYearBegin, $financialYearEnd, $user);
        }

        return [
            'begin' => clone $weekBegin,
            'end' => clone $weekEnd,
            'stats' => $this->repository->getDailyStats($user, $weekBegin, $weekEnd),
            'thisMonth' => $thisMonth,
            'lastWeekInYear' => $lastWeekInYear,
            'lastWeekInLastYear' => $lastWeekInLastYear,
            'day' => $this->repository->getStatistic('duration', $dayBegin, $dayEnd, $user),
            'week' => $this->repository->getStatistic('duration', $weekBegin, $weekEnd, $user),
            'month' => $this->repository->getStatistic('duration', $monthBegin, $monthEnd, $user),
            'year' => $yearData,
            'financial' => $financialYearData,
            'financialBegin' => $financialYearBegin,
        ];
    }
}
