<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\TimesheetExported;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TimesheetExported::class)]
class TimesheetExportedTest extends AbstractConstraintTestCase
{
    public function testIsTimesheetConstraint(): void
    {
        $this->assertTimesheetConstraint(new TimesheetExported());
    }
}
