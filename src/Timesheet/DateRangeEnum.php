<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

enum DateRangeEnum: string
{
    case TODAY = 'today';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
    case TOTAL = 'total';
    case FINANCIAL = 'financial';
}
