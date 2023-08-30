<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

enum CalendarSourceType: string
{
    case GOOGLE = 'google';
    case ICAL = 'ical';
    case JSON = 'json';
    case TIMESHEET = 'timesheet';
}
