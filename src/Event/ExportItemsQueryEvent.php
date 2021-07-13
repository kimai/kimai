<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Repository\Query\ExportQuery;
use Symfony\Contracts\EventDispatcher\Event;

class ExportItemsQueryEvent extends Event
{
    private $query;

    public function __construct(ExportQuery $query)
    {
        $this->query = $query;
    }

    public function getExportQuery(): ExportQuery
    {
        return $this->query;
    }
}
