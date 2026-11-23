<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Repository\Query\TimesheetQuery;

/**
 * Dynamically find possible meta fields for the quick-entry screen.
 *
 * @method TimesheetQuery getQuery()
 */
final class QuickEntryMetaDisplayEvent extends AbstractMetaDisplayEvent
{
    public function __construct(TimesheetQuery $query)
    {
        parent::__construct($query, 'quick-entry');
    }
}
