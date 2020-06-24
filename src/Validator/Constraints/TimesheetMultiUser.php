<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Doctrine\Common\Annotations\Annotation\Target;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "METHOD", "ANNOTATION"})
 */
class TimesheetMultiUser extends Constraint
{
    public const MISSING_USER_OR_TEAM = 'ts-multi-user-01';

    protected static $errorNames = [
        self::MISSING_USER_OR_TEAM => 'You must select at least one user or team.',
    ];

    public $message = 'This form has invalid settings.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
