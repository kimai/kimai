<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Repository\Query\ProjectQuery;

/**
 * Dynamically find possible meta fields for a project query.
 *
 * @method ProjectQuery getQuery()
 */
final class ProjectMetaDisplayEvent extends AbstractMetaDisplayEvent
{
    public const EXPORT = 'export';
    public const PROJECT = 'project';

    public function __construct(ProjectQuery $query, string $location)
    {
        parent::__construct($query, $location);
    }
}
