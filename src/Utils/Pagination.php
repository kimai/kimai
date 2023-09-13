<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Repository\Query\BaseQuery;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;

final class Pagination extends Pagerfanta
{
    public function __construct(AdapterInterface $adapter, ?BaseQuery $query = null)
    {
        parent::__construct($adapter);

        if ($query === null || !$query->isApiCall()) {
            $this->setNormalizeOutOfRangePages(true);
        }

        if ($query !== null) {
            $this->setMaxPerPage($query->getPageSize());
            $this->setCurrentPage($query->getPage());
        }
    }
}
