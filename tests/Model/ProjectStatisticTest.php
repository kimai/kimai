<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\CustomerStatistic;

/**
 * @covers \App\Model\ProjectStatistic
 */
class ProjectStatisticTest extends AbstractTimesheetCountedStatisticTest
{
    public function testDefaultValues(): void
    {
        $this->assertDefaultValues(new CustomerStatistic());
    }

    public function testSetter(): void
    {
        $this->assertSetter(new CustomerStatistic());
    }

    public function testJsonSerialize(): void
    {
        $this->assertJsonSerialize(new CustomerStatistic());
    }
}
