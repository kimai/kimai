<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\Customer;
use App\Entity\Timesheet;
use App\Export\ExportablePreviewResult;
use App\Export\ExportableResultInterface;
use App\Export\ExportableResultStatistic;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExportablePreviewResult::class)]
class ExportablePreviewResultTest extends TestCase
{
    public function testEmpty(): void
    {
        $sut = new ExportablePreviewResult([], []);

        self::assertInstanceOf(ExportableResultInterface::class, $sut);
        self::assertSame([], $sut->getExportItems());
        self::assertSame([], $sut->getStatistic());
    }

    public function testStatisticIsIndependentFromItems(): void
    {
        $items = [new Timesheet()];
        // the statistic covers all matching items, while the preview only contains a subset
        $statistic = [new ExportableResultStatistic(new Customer('foo'), 100, 3600, 10.5)];

        $sut = new ExportablePreviewResult($items, $statistic);

        self::assertSame($items, $sut->getExportItems());
        self::assertSame($statistic, $sut->getStatistic());
        self::assertSame($statistic, $sut->getStatistic(['foo' => 'bar']));
    }
}
