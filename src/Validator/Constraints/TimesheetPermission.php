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
class TimesheetPermission extends Constraint
{
    public const START_DISALLOWED = 'xd5hffg-xfh9f3-426a-83d7-1f4633h85d81';

    protected static $errorNames = [
        self::START_DISALLOWED => 'You are not allowed to start this timesheet record.',
    ];

    public $message = 'This timesheet has invalid settings.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
