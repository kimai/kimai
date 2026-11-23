<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Result;

use App\Repository\Result\TimesheetResultStatistic;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimesheetResultStatistic::class)]
class TimesheetResultStatisticTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new TimesheetResultStatistic(13, 7705);
        self::assertSame(13, $sut->getCount());
        self::assertSame(7705, $sut->getDuration());
    }
}
