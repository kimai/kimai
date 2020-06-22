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
class TimesheetMultiUpdate extends Constraint
{
    public const MISSING_ACTIVITY_ERROR = 'ts-multi-update-84';
    public const MISSING_PROJECT_ERROR = 'ts-multi-update-85';
    public const ACTIVITY_PROJECT_MISMATCH_ERROR = 'ts-multi-update-86';
    public const DISABLED_ACTIVITY_ERROR = 'ts-multi-update-87';
    public const DISABLED_PROJECT_ERROR = 'ts-multi-update-88';
    public const DISABLED_CUSTOMER_ERROR = 'ts-multi-update-89';
    public const HOURLY_RATE_FIXED_RATE = 'ts-multi-update-90';

    protected static $errorNames = [
        self::MISSING_ACTIVITY_ERROR => 'You need to choose an activity, if the project should be changed.',
        self::MISSING_PROJECT_ERROR => 'A timesheet must have a project.',
        self::ACTIVITY_PROJECT_MISMATCH_ERROR => 'Project mismatch: chosen project does not match the activity project.',
        self::DISABLED_ACTIVITY_ERROR => 'Cannot start a disabled activity.',
        self::DISABLED_PROJECT_ERROR => 'Cannot start a disabled project.',
        self::DISABLED_CUSTOMER_ERROR => 'Cannot start a disabled customer.',
        self::HOURLY_RATE_FIXED_RATE => 'Cannot set hourly rate and fixed rate at the same time.',
    ];

    public $message = 'This form has invalid settings.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
