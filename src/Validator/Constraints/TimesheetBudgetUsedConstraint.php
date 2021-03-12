<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Validator\TimesheetBudgetUsedValidator;

final class TimesheetBudgetUsedConstraint extends TimesheetConstraint
{
    public const BUDGET_SPENT = 'kimai-timesheet-budget-used-01';

    // same messages, so we can re-use the validation translation!
    public $messageRate = 'The budget is completely used.';
    public $messageTime = 'The budget is completely used.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return TimesheetBudgetUsedValidator::class;
    }
}
