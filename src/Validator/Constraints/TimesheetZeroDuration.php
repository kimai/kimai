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
final class TimesheetZeroDuration extends Constraint
{
    public const string ZERO_DURATION_ERROR = 'kimai-timesheet-zero-duration-01';

    protected const array ERROR_NAMES = [
        self::ZERO_DURATION_ERROR => 'Duration cannot be zero.',
    ];

    public string $message = 'Duration cannot be zero.';

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? $this->message;
        parent::__construct();
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
