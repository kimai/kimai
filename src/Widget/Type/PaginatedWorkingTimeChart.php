<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;
use DateTime;

final class PaginatedWorkingTimeChart extends SimpleWidget implements UserWidget
{
    /**
     * @var TimesheetRepository
     */
    private $repository;

    public function __construct(TimesheetRepository $repository)
    {
        $this->repository = $repository;
        $this->setId('PaginatedWorkingTimeChart');
        $this->setTitle('stats.yourWorkingHours');

        $this->setOptions([
            'year' => (new DateTime('now'))->format('o'),
            'week' => (new DateTime('now'))->format('W'),
            'type' => 'bar',
        ]);
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

        if (!\in_array($options['type'], ['bar', 'line'])) {
            $options['type'] = 'bar';
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
        $weekEnd = ($dateTimeFactory->createDateTime())->setISODate($year, $week, 7)->setTime(23, 59, 59);

        $weekBegin = $dateTimeFactory->getStartOfWeek($weekBegin);
        $weekEnd = $dateTimeFactory->getEndOfWeek($weekEnd);

        $lastWeekInYear = $this->getLastWeekInYear($year);
        $lastWeekInLastYear = $this->getLastWeekInYear($year - 1);

        $thisMonth = clone $weekBegin;
        if ((int) $week === 1) {
            $thisMonth = ($dateTimeFactory->createDateTime())->setISODate($year, $week, 1)->setTime(0, 0, 0);
        }

        return [
            'begin' => clone $weekBegin,
            'end' => clone $weekEnd,
            'stats' => $this->repository->getDailyStats($user, $weekBegin, $weekEnd),
            'thisMonth' => $thisMonth,
            'lastWeekInYear' => $lastWeekInYear,
            'lastWeekInLastYear' => $lastWeekInLastYear,
            'day' => $this->repository->getStatistic(
                'duration',
                $dateTimeFactory->createDateTime('00:00:00'),
                $dateTimeFactory->createDateTime('23:59:59'),
                $user
            ),
            'week' => $this->repository->getStatistic(
                'duration',
                $weekBegin,
                $weekEnd,
                $user
            ),
            'month' => $this->repository->getStatistic(
                'duration',
                (clone $weekBegin)->setDate($weekBegin->format('Y'), $weekBegin->format('n'), 1)->setTime(0, 0, 0),
                (clone $weekBegin)->setDate($weekBegin->format('Y'), $weekBegin->format('n'), $weekBegin->format('t'))->setTime(23, 59, 59),
                $user
            ),
            'year' => $this->repository->getStatistic(
                'duration',
                $dateTimeFactory->createDateTime(sprintf('01 january %s 00:00:00', $year)),
                $dateTimeFactory->createDateTime(sprintf('31 december %s 23:59:59', $year)),
                $user
            ),
        ];
    }
}
