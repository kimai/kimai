<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetBudgetUsed extends TimesheetConstraint
{
    // same messages, so we can re-use the validation translation!
    public $messageRate = 'The budget is completely used.';
    public $messageTime = 'The budget is completely used.';
    public $messagePermission = 'Sorry, the budget is used up.';
}
