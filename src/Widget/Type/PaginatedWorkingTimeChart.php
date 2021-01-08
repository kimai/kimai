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

    private function getDate(\DateTimeZone $timezone, $year, $week, $day, $hour, $minute, $second)
    {
        $now = new DateTime('now', $timezone);
        $now->setISODate($year, $week, $day);
        $now->setTime($hour, $minute, $second);

        return $now;
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

        $timezone = new \DateTimeZone($user->getTimezone());

        $weekBegin = $this->getDate($timezone, $options['year'], $options['week'], 1, 0, 0, 0);
        $weekEnd = $this->getDate($timezone, $options['year'], $options['week'], 7, 23, 59, 59);

        $lastWeekInYear = $this->getLastWeekInYear($options['year']);
        $lastWeekInLastYear = $this->getLastWeekInYear($options['year'] - 1);

        $thisMonth = clone $weekBegin;
        if ((int) $options['week'] === 1) {
            $thisMonth = (new DateTime('now', $timezone))->setISODate($options['year'], $options['week'], 7)->setTime(0, 0, 0);
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
                new DateTime('00:00:00', $timezone),
                new DateTime('23:59:59', $timezone),
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
                new DateTime(sprintf('01 january %s 00:00:00', $options['year']), $timezone),
                new DateTime(sprintf('31 december %s 23:59:59', $options['year']), $timezone),
                $user
            ),
        ];
    }
}
