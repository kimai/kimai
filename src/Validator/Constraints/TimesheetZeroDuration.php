<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetZeroDuration extends TimesheetConstraint
{
    public const ZERO_DURATION_ERROR = 'kimai-timesheet-zero-duration-01';

    protected const ERROR_NAMES = [
        self::ZERO_DURATION_ERROR => 'Duration cannot be zero.',
    ];

    public string $message = 'Duration cannot be zero.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
