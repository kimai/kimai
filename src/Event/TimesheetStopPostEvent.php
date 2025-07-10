<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Webhook\Attribute\AsWebhook;

#[AsWebhook(name: 'timesheet.stopped', description: 'Triggered after a timesheet was stopped', payload: 'object.getTimesheet()')]
final class TimesheetStopPostEvent extends AbstractTimesheetEvent
{
}
