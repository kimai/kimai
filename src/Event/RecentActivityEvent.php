<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Timesheet;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class RecentActivityEvent extends Event
{
    /**
     * @param User $user
     * @param Timesheet[] $recentActivities
     */
    public function __construct(private User $user, private array $recentActivities)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Timesheet[]
     */
    public function getRecentActivities(): array
    {
        return array_values($this->recentActivities);
    }

    public function addRecentActivity(Timesheet $recentActivity): RecentActivityEvent
    {
        $this->recentActivities[] = $recentActivity;

        return $this;
    }

    public function removeRecentActivity(Timesheet $recentActivity): bool
    {
        $key = array_search($recentActivity, $this->recentActivities, true);
        if (false === $key) {
            return false;
        }

        unset($this->recentActivities[$key]);

        return true;
    }
}
