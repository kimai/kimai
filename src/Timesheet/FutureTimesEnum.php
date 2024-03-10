<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

enum FutureTimesEnum: string
{
    case ALLOW = 'allow';
    case DENY = 'deny';
    case END_OF_DAY = 'end_of_day';
    case END_OF_WEEK = 'end_of_week';
}
