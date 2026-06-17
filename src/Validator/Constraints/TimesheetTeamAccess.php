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
final class TimesheetTeamAccess extends Constraint
{
    public const PROJECT_ACCESS_ERROR = 'kimai-timesheet-team-project';
    public const ACTIVITY_ACCESS_ERROR = 'kimai-timesheet-team-activity';

    protected const ERROR_NAMES = [
        self::PROJECT_ACCESS_ERROR => 'You are not allowed to use this project.',
        self::ACTIVITY_ACCESS_ERROR => 'You are not allowed to use this activity.',
    ];

    public string $message = 'This timesheet has invalid settings.';

    public function __construct(?string $message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct();
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
