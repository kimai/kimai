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
    public const MISSING_ACTIVITY_ERROR = 'yd5hffg-dsfef3-426a-83d7-1f2d33hs5d84';
    public const MISSING_PROJECT_ERROR = 'yd5hffg-dsfef3-426a-83d7-1f2d33hs5d85';
    public const ACTIVITY_PROJECT_MISMATCH_ERROR = 'xy5hffg-dsfef3-426a-83d7-1f2d33hs5d86';
    public const DISABLED_ACTIVITY_ERROR = 'yd5hffg-dsfef3-426a-83d7-1f2d33hs5d87';
    public const DISABLED_PROJECT_ERROR = 'yd5hffg-dsfef3-426a-83d7-1f2d33hs5d88';
    public const DISABLED_CUSTOMER_ERROR = 'yd5hffg-dsfef3-426a-83d7-1f2d33hs5d89';

    protected static $errorNames = [
        self::MISSING_ACTIVITY_ERROR => 'A timesheet must have an activity.',
        self::MISSING_PROJECT_ERROR => 'A timesheet must have a project.',
        self::ACTIVITY_PROJECT_MISMATCH_ERROR => 'Project mismatch: chosen project does not match the activity project.',
        self::DISABLED_ACTIVITY_ERROR => 'Cannot start a disabled activity.',
        self::DISABLED_PROJECT_ERROR => 'Cannot start a disabled project.',
        self::DISABLED_CUSTOMER_ERROR => 'Cannot start a disabled customer.',
    ];

    public $message = 'This form has invalid settings.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
