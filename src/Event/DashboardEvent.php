<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use App\Model\TimesheetGlobalStatistic;
use App\Model\WidgetRow;
use Symfony\Component\EventDispatcher\Event;

class DashboardEvent extends Event
{
    public const DASHBOARD = 'app.dashboard';

    /**
     * @var User
     */
    protected $user;
    /**
     * @var WidgetRow[]
     */
    protected $widgetRows = [];

    /**
     * @param User $user
     * @param TimesheetGlobalStatistic $timesheetStatistic
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function addWidgetRow(WidgetRow $row)
    {
        $this->widgetRows[] = $row;
    }

    /**
     * @return WidgetRow[]
     */
    public function getWidgetRows()
    {
        return $this->widgetRows;
    }
}
