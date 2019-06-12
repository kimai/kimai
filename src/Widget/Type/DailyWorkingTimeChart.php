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
use DateTime;

class DailyWorkingTimeChart extends SimpleWidget
{
    /**
     * @var TimesheetRepository
     */
    protected $repository;

    public function __construct(TimesheetRepository $repository, CurrentUser $user)
    {
        $this->repository = $repository;
        $this->setId('DailyWorkingTimeChart');
        $this->setTitle('stats.yourWorkingHours');
        $this->setOptions([
            'begin' => 'monday this week 00:00:00',
            'end' => 'sunday this week 23:59:59',
            'color' => '',
            'user' => $user->getUser(),
            'type' => 'bar',
            'id' => uniqid('DailyWorkingTimeChart_'),
        ]);
    }

    public function getData()
    {
        if (!in_array($this->getOption('type'), ['bar', 'line'])) {
            $this->setOption('type', 'bar');
        }
        $user = $this->getOption('user');
        $begin = new DateTime($this->getOption('begin'));
        $end = new DateTime($this->getOption('end'));

        return $this->repository->getDailyStats($user, $begin, $end);
    }
}
