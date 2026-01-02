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
final class TimesheetFutureTimes extends Constraint
{
    public const string BEGIN_IN_FUTURE_ERROR = 'kimai-timesheet-future-times-01';
    public const string END_IN_FUTURE_ERROR = 'kimai-timesheet-future-times-02';

    protected const array ERROR_NAMES = [
        self::BEGIN_IN_FUTURE_ERROR => 'The begin date cannot be in the future.',
        self::END_IN_FUTURE_ERROR => 'The end date cannot be in the future.',
    ];

    public string $message = 'The date cannot be in the future.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
