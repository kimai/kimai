<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\TimesheetConstraint;
use App\Validator\Constraints\TimesheetZeroDuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimesheetZeroDuration::class)]
class TimesheetZeroDurationTest extends TestCase
{
    public function testIsTimesheetConstraint(): void
    {
        self::assertInstanceOf(TimesheetConstraint::class, new TimesheetZeroDuration());
    }
}
