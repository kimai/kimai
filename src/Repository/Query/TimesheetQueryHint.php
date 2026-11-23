<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

enum TimesheetQueryHint
{
    case USER_PREFERENCES;
    case CUSTOMER_META_FIELDS;
    case PROJECT_META_FIELDS;
    case ACTIVITY_META_FIELDS;
}
