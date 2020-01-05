<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

/**
 * Query class for Repositories with a visibility field.
 *
 * @deprecated since 1.7, will be removed with 2.0
 */
class VisibilityQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;
}
