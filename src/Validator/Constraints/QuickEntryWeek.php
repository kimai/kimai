<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class QuickEntryWeek extends Constraint
{
    public const RECORD_OVERLAPPING = 'quick-entry-week-01';
    public const DAY_CAPACITY_EXCEEDED = 'quick-entry-week-02';

    public string $messageDayCapacityExceeded = 'The entries for {{ day }} exceed the hours available on that day.';
}
