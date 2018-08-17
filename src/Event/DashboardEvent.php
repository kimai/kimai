<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use App\Model\DashboardSection;
use Symfony\Component\EventDispatcher\Event;

class DashboardEvent extends Event
{
    public const DASHBOARD = 'app.dashboard';

    /**
     * @var User
     */
    protected $user;
    /**
     * @var DashboardSection[]
     */
    protected $widgetRows = [];

    /**
     * @param User $user
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

    /**
     * @param DashboardSection $row
     * @return DashboardEvent
     */
    public function addSection(DashboardSection $row)
    {
        $this->widgetRows[] = $row;

        return $this;
    }

    /**
     * @return DashboardSection[]
     */
    public function getSections()
    {
        return $this->widgetRows;
    }
}
