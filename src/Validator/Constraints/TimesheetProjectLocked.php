<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetProjectLocked extends TimesheetConstraint
{
    public const PERIOD_LOCKED = 'kimai-timesheet-project-locked-01';

    protected const ERROR_NAMES = [
        self::PERIOD_LOCKED => 'The project is locked until %date%, please choose a later date.',
    ];

    public string $message = 'The project is locked until %date%, please choose a later date.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
