<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class Role extends Constraint
{
    const ROLE_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d84';

    protected static $errorNames = array(
        self::ROLE_ERROR => 'ROLE_ERROR',
    );

    public $message = 'This value is not a valid role.';
}
