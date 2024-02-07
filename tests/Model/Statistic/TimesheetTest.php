<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model\Statistic;

use App\Model\Statistic\Timesheet;

/**
 * @covers \App\Model\Statistic\Timesheet
 */
class TimesheetTest extends AbstractTimesheetTest
{
    public function testDefaultValues(): void
    {
        $sut = new Timesheet();
        $this->assertDefaultValues($sut);
    }

    public function testSetter(): void
    {
        $sut = new Timesheet();
        $this->assertSetter($sut);
    }
}
