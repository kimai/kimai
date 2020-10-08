<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetLockdown extends TimesheetConstraint
{
    public const PERIOD_LOCKED = 'kimai-timesheet-lockdown-01';

    protected static $errorNames = [
        self::PERIOD_LOCKED => 'This period is locked, please choose a later date.',
    ];

    public $message = 'This period is locked, please choose a later date.';
    /**
     * @var \DateTime|string|null
     */
    public $now;

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
