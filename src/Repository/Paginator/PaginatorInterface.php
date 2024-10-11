<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Paginator;

use Pagerfanta\Adapter\AdapterInterface;

/**
 * @template-covariant T
 * @extends AdapterInterface<T>
 */
interface PaginatorInterface extends AdapterInterface
{
    /**
     * Returns all available results without pagination.
     *
     * @return iterable<array-key, T>
     */
    public function getAll(): iterable;
}
