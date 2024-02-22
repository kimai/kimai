<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model\Statistic;

use App\Model\Statistic\Timesheet;
use PHPUnit\Framework\TestCase;

abstract class AbstractTimesheetTest extends TestCase
{
    public function assertDefaultValues(Timesheet $sut): void
    {
        self::assertSame(0.0, $sut->getRate());
        self::assertSame(0, $sut->getDuration());
        self::assertSame(0, $sut->getValue());
        self::assertSame(0.0, $sut->getInternalRate());
        self::assertSame(0, $sut->getTotalDuration());
        self::assertSame(0.0, $sut->getTotalRate());
        self::assertSame(0.0, $sut->getTotalInternalRate());
    }

    public function assertSetter(Timesheet $sut): void
    {
        $sut->setTotalInternalRate(5485.84);
        $sut->setTotalRate(1234.23);
        $sut->setTotalDuration(567);

        self::assertSame(1234.23, $sut->getRate());
        self::assertSame(567, $sut->getDuration());
        self::assertSame(567, $sut->getValue());
        self::assertSame(5485.84, $sut->getInternalRate());
        self::assertSame(567, $sut->getTotalDuration());
        self::assertSame(1234.23, $sut->getTotalRate());
        self::assertSame(5485.84, $sut->getTotalInternalRate());
    }
}
