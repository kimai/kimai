<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiTest\AppBundle\Repository\Query;

use AppBundle\Repository\Query\VisibilityInterface;
use AppBundle\Repository\Query\VisibilityTrait;

/**
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class VisibilityTraitImplementation implements VisibilityInterface
{
    use VisibilityTrait;
}
