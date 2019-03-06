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
    public const MISSING_BEGIN_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d81';
    public const END_BEFORE_BEGIN_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d82';
    public const BEGIN_IN_FUTURE_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d83';
    public const MISSING_ACTIVITY_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d84';
    public const MISSING_PROJECT_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d85';
    public const ACTIVITY_PROJECT_MISMATCH_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d86';
    public const DISABLED_ACTIVITY_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d87';
    public const DISABLED_PROJECT_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d88';
    public const DISABLED_CUSTOMER_ERROR = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d89';
    public const START_DISALLOWED = 'xd5hffg-dsfef3-426a-83d7-1f2d33hs5d90';

    protected static $errorNames = [
        self::MISSING_BEGIN_ERROR => 'You must submit a begin date.',
        self::END_BEFORE_BEGIN_ERROR => 'End date must not be earlier then start date.',
        self::BEGIN_IN_FUTURE_ERROR => 'The begin date cannot be in the future.',
        self::MISSING_ACTIVITY_ERROR => 'A timesheet must have an activity.',
        self::MISSING_PROJECT_ERROR => 'A timesheet must have a project.',
        self::ACTIVITY_PROJECT_MISMATCH_ERROR => 'Project mismatch, project specific activity and timesheet project are different.',
        self::DISABLED_ACTIVITY_ERROR => 'Cannot start a disabled activity.',
        self::DISABLED_PROJECT_ERROR => 'Cannot start a disabled project.',
        self::DISABLED_CUSTOMER_ERROR => 'Cannot start a disabled customer.',
        self::START_DISALLOWED => 'You are not allowed to start this timesheet record.',
    ];

    public $message = 'This timesheet has invalid settings.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
