<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model\Statistic;

use App\Model\Statistic\BudgetStatistic;
use App\Tests\Model\AbstractTimesheetCountedStatisticTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BudgetStatistic::class)]
class BudgetStatisticTest extends AbstractTimesheetCountedStatisticTestCase
{
    public function testDefaultValues(): void
    {
        $this->assertDefaultValues(new BudgetStatistic());
    }

    public function testSetter(): void
    {
        $this->assertSetter(new BudgetStatistic());
    }

    public function testJsonSerialize(): void
    {
        $this->assertJsonSerialize(new BudgetStatistic());
    }
}
