<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

enum SummaryType
{
    case DAYS;
    case DURATION;
    case WORKING_TIME;
}
