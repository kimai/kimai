<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

/**
 * Can be used for advanced queries with the: CustomerRepository
 */
class CustomerQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;

    public const CUSTOMER_ORDER_ALLOWED = ['id', 'name', 'comment', 'country', 'number'];

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'name',
        ]);
    }
}
