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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Report::class)]
class ReportTest extends TestCase
{
    public function testEmptyObject(): void
    {
        $report = new Report('id', 'route', 'label', 'reporting');
        self::assertInstanceOf(ReportInterface::class, $report);
        self::assertEquals('id', $report->getId());
        self::assertEquals('route', $report->getRoute());
        self::assertEquals('label', $report->getLabel());
        self::assertEquals('reporting', $report->getReportIcon());
        self::assertEquals('reporting', $report->getTranslationDomain());

        $report = new Report('id', 'route', 'label', 'foo', 'bar');
        self::assertEquals('foo', $report->getReportIcon());
        self::assertEquals('bar', $report->getTranslationDomain());
    }
}
