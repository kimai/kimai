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

class DailyWorkingTimeChart extends SimpleWidget
{
    public const DEFAULT_CHART = 'bar';

    /**
     * @var TimesheetRepository
     */
    protected $repository;
    /**
     * @var UserDateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(TimesheetRepository $repository, CurrentUser $user, UserDateTimeFactory $dateTime)
    {
        $this->repository = $repository;
        $this->dateTimeFactory = $dateTime;
        $this->setId('DailyWorkingTimeChart');
        $this->setTitle('stats.yourWorkingHours');
        $this->setOptions([
            'begin' => 'monday this week 00:00:00',
            'end' => 'sunday this week 23:59:59',
            'color' => '',
            'user' => $user->getUser(),
            'type' => self::DEFAULT_CHART,
            'id' => '',
        ]);
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
        if ($options['begin'] instanceof DateTime) {
            $begin = $options['begin'];
        } else {
            $begin = new DateTime($options['begin'], $this->dateTimeFactory->getTimezone());
        }

        if ($options['end'] instanceof DateTime) {
            $end = $options['end'];
        } else {
            $end = new DateTime($options['end'], $this->dateTimeFactory->getTimezone());
        }

        return $this->repository->getDailyStats($user, $begin, $end);
    }
}
