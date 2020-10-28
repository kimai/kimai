<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class HexColor extends Constraint
{
    public const HEX_COLOR_ERROR = 'xd5hffg-dsfef3-426a-83d7-2g8jkfr56d84';

    protected static $errorNames = [
        self::HEX_COLOR_ERROR => 'HEX_COLOR_ERROR',
    ];

    public $message = 'The given value is not a valid hexadecimal color.';
}
