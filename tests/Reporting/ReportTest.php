<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Reporting\Report;
use App\Reporting\ReportInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\Report
 */
class ReportTest extends TestCase
{
    public function testEmptyObject()
    {
        $report = new Report('id', 'route', 'label');
        self::assertInstanceOf(ReportInterface::class, $report);
        self::assertEquals('id', $report->getId());
        self::assertEquals('route', $report->getRoute());
        self::assertEquals('label', $report->getLabel());
    }
}
