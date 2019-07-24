<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Paginator;

use Pagerfanta\Adapter\AdapterInterface;

interface PaginatorInterface extends AdapterInterface
{
    /**
     * Returns all available results without pagination.
     *
     * @return iterable
     */
    public function getAll(): iterable;
}
