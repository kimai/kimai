<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

class TagQuery extends BaseQuery
{
    use VisibilityTrait;

    public const TAG_ORDER_ALLOWED = ['name', 'amount'];

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'name',
            'visibility' => VisibilityInterface::SHOW_VISIBLE,
        ]);
    }
}
