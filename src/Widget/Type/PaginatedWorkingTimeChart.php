<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Security\CurrentUser;
use App\Timesheet\UserDateTimeFactory;
use DateTime;

final class PaginatedWorkingTimeChart extends SimpleWidget
{
    /**
     * @var TimesheetRepository
     */
    private $repository;
    /**
     * @var UserDateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(TimesheetRepository $repository, CurrentUser $user, UserDateTimeFactory $dateTime)
    {
        $this->repository = $repository;
        $this->dateTimeFactory = $dateTime;
        $this->setId('PaginatedWorkingTimeChart');
        $this->setTitle('stats.yourWorkingHours');

        $this->setOptions([
            'year' => (new DateTime('now', $this->dateTimeFactory->getTimezone()))->format('Y'),
            'week' => (new DateTime('now', $this->dateTimeFactory->getTimezone()))->format('W'),
            'user' => $user->getUser(),
            'type' => 'bar',
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

    private function getDate($year, $week, $day, $hour, $minute, $second)
    {
        $now = new DateTime('now', $this->dateTimeFactory->getTimezone());
        $now->setISODate($year, $week, $day);
        $now->setTime($hour, $minute, $second);

        return $now;
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);
        $user = $options['user'];

        $weekBegin = $this->getDate($options['year'], $options['week'], 1, 0, 0, 0);
        $weekEnd = $this->getDate($options['year'], $options['week'], 7, 23, 59, 59);

        return [
            'begin' => clone $weekBegin,
            'end' => clone $weekEnd,
            'stats' => $this->repository->getDailyStats($user, $weekBegin, $weekEnd),
            'day' => $this->repository->getStatistic(
                'duration',
                new DateTime('00:00:00', $this->dateTimeFactory->getTimezone()),
                new DateTime('23:59:59', $this->dateTimeFactory->getTimezone()),
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
                new DateTime(sprintf('01 january %s 00:00:00', $options['year']), $this->dateTimeFactory->getTimezone()),
                new DateTime(sprintf('31 december %s 23:59:59', $options['year']), $this->dateTimeFactory->getTimezone()),
                $user
            ),
        ];
    }
}
