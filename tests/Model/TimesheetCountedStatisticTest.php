<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\TimesheetCountedStatistic;

/**
 * @covers \App\Model\TimesheetCountedStatistic
 */
class TimesheetCountedStatisticTest extends AbstractTimesheetCountedStatisticTestCase
{
    public function testDefaultValues(): void
    {
        $this->assertDefaultValues(new TimesheetCountedStatistic());
    }

    public function testSetter(): void
    {
        $this->assertSetter(new TimesheetCountedStatistic());
    }

    public function testJsonSerialize(): void
    {
        $this->assertJsonSerialize(new TimesheetCountedStatistic());
    }
}
