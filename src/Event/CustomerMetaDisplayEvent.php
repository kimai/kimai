<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Repository\Query\CustomerQuery;

/**
 * Dynamically find possible meta fields for a customer query.
 *
 * @method CustomerQuery getQuery()
 */
final class CustomerMetaDisplayEvent extends AbstractMetaDisplayEvent
{
    public const EXPORT = 'export';
    public const CUSTOMER = 'customer';

    public function __construct(CustomerQuery $query, string $location)
    {
        parent::__construct($query, $location);
    }
}
