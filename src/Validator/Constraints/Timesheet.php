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
class Timesheet extends Constraint
{
    /** @deprecated since 1.15.3 - use TimesheetBasic::MISSING_BEGIN_ERROR instead */
    public const MISSING_BEGIN_ERROR = TimesheetBasic::MISSING_BEGIN_ERROR;
    /** @deprecated since 1.15.3 - use TimesheetBasic::END_BEFORE_BEGIN_ERROR instead */
    public const END_BEFORE_BEGIN_ERROR = TimesheetBasic::END_BEFORE_BEGIN_ERROR;
    /** @deprecated since 1.15.3 - use TimesheetBasic::MISSING_ACTIVITY_ERROR instead */
    public const MISSING_ACTIVITY_ERROR = TimesheetBasic::MISSING_ACTIVITY_ERROR;
    /** @deprecated since 1.15.3 - use TimesheetBasic::MISSING_PROJECT_ERROR instead */
    public const MISSING_PROJECT_ERROR = TimesheetBasic::MISSING_PROJECT_ERROR;
    /** @deprecated since 1.15.3 - use TimesheetBasic::ACTIVITY_PROJECT_MISMATCH_ERROR instead */
    public const ACTIVITY_PROJECT_MISMATCH_ERROR = TimesheetBasic::ACTIVITY_PROJECT_MISMATCH_ERROR;
    /** @deprecated since 1.15.3 - use TimesheetBasic::DISABLED_ACTIVITY_ERROR instead */
    public const DISABLED_ACTIVITY_ERROR = TimesheetBasic::DISABLED_ACTIVITY_ERROR;
    /** @deprecated since 1.15.3 - use TimesheetBasic::DISABLED_PROJECT_ERROR instead */
    public const DISABLED_PROJECT_ERROR = TimesheetBasic::DISABLED_PROJECT_ERROR;
    /** @deprecated since 1.15.3 - use TimesheetBasic::DISABLED_CUSTOMER_ERROR instead */
    public const DISABLED_CUSTOMER_ERROR = TimesheetBasic::DISABLED_CUSTOMER_ERROR;
    /** @deprecated since 1.15.3 - use TimesheetBasic::PROJECT_NOT_STARTED instead */
    public const PROJECT_NOT_STARTED = TimesheetBasic::PROJECT_NOT_STARTED;
    /** @deprecated since 1.15.3 - use TimesheetBasic::PROJECT_ALREADY_ENDED instead */
    public const PROJECT_ALREADY_ENDED = TimesheetBasic::PROJECT_ALREADY_ENDED;

    protected static $errorNames = [
        self::MISSING_BEGIN_ERROR => 'You must submit a begin date.',
        self::END_BEFORE_BEGIN_ERROR => 'End date must not be earlier then start date.',
        self::MISSING_ACTIVITY_ERROR => 'An activity needs to be selected.',
        self::MISSING_PROJECT_ERROR => 'A project needs to be selected.',
        self::ACTIVITY_PROJECT_MISMATCH_ERROR => 'Project mismatch, project specific activity and timesheet project are different.',
        self::DISABLED_ACTIVITY_ERROR => 'Cannot start a disabled activity.',
        self::DISABLED_PROJECT_ERROR => 'Cannot start a disabled project.',
        self::DISABLED_CUSTOMER_ERROR => 'Cannot start a disabled customer.',
        self::PROJECT_NOT_STARTED => 'The project has not started at that time.',
        self::PROJECT_ALREADY_ENDED => 'The project is finished at that time.',
    ];

    public $message = 'This timesheet has invalid settings.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
