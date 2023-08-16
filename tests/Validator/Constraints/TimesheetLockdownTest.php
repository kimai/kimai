<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\TimesheetConstraint;
use App\Validator\Constraints\TimesheetLockdown;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Validator\Constraints\TimesheetLockdown
 */
class TimesheetLockdownTest extends TestCase
{
    public function testIsTimesheetConstraint(): void
    {
        self::assertInstanceOf(TimesheetConstraint::class, new TimesheetLockdown());
    }
}
