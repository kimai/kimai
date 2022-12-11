<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

final class TimesheetTeamSubscriber extends AbstractTimesheetSubscriber
{
    public static function getActionName(): string
    {
        return 'timesheet_team';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $this->timesheetActions($event, 'admin_timesheet_edit', 'admin_timesheet_duplicate');
    }
}
