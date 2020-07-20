<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Repository\Query\ActivityQuery;

/**
 * Dynamically find possible meta fields for a activity query.
 *
 * @method ActivityQuery getQuery()
 */
final class ActivityMetaDisplayEvent extends AbstractMetaDisplayEvent
{
    public const EXPORT = 'export';
    public const ACTIVITY = 'activity';

    public function __construct(ActivityQuery $query, string $location)
    {
        parent::__construct($query, $location);
    }
}
