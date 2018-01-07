<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Repository\Query;

use AppBundle\Repository\Query\BaseQuery;
use AppBundle\Repository\Query\VisibilityInterface;
use AppBundle\Repository\Query\VisibilityTrait;

/**
 * Can be used for advanced queries with the: ActivityRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;
}
