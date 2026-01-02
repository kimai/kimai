<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Validator\Attribute\TimesheetConstraint;
use Symfony\Component\Validator\Constraint;

#[TimesheetConstraint]
final class TimesheetLockdown extends Constraint
{
    public const string PERIOD_LOCKED = 'kimai-timesheet-lockdown-01';

    protected const array ERROR_NAMES = [
        self::PERIOD_LOCKED => 'This period is locked, please choose a later date.',
    ];

    public string $message = 'This period is locked, please choose a later date.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
