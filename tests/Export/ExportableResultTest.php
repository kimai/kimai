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
use App\Export\ExportableResult;
use App\Export\ExportableResultInterface;
use App\Export\ExportableResultStatistic;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExportableResult::class)]
class ExportableResultTest extends TestCase
{
    private function createCustomer(int $id): Customer
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn($id);

        return $customer;
    }

    public function testEmpty(): void
    {
        $sut = new ExportableResult([]);

        self::assertInstanceOf(ExportableResultInterface::class, $sut);
        self::assertSame([], $sut->getExportItems());
        self::assertSame([], $sut->getStatistic());
    }

    public function testGetExportItemsMergesAllResultsInOrder(): void
    {
        $item1 = new Timesheet();
        $item2 = new Timesheet();
        $item3 = new Timesheet();

        $sut = new ExportableResult([
            new ExportablePreviewResult([$item1, $item2], []),
            new ExportablePreviewResult([], []),
            new ExportablePreviewResult([$item3], []),
        ]);

        self::assertSame([$item1, $item2, $item3], $sut->getExportItems());
    }

    public function testGetStatisticMergesByCustomer(): void
    {
        $customer1 = $this->createCustomer(1);
        $customer2 = $this->createCustomer(2);

        $sut = new ExportableResult([
            new ExportablePreviewResult([], [
                new ExportableResultStatistic($customer1, 2, 100, 1.5),
                new ExportableResultStatistic($customer2, 3, 200, 2.5),
            ]),
            new ExportablePreviewResult([], [
                new ExportableResultStatistic($customer1, 4, 300, 3.5),
            ]),
        ]);

        $statistic = $sut->getStatistic();

        self::assertCount(2, $statistic);

        self::assertSame($customer1, $statistic[0]->getCustomer());
        self::assertSame(6, $statistic[0]->getCount());
        self::assertSame(400, $statistic[0]->getDuration());
        self::assertSame(5.0, $statistic[0]->getRate());

        self::assertSame($customer2, $statistic[1]->getCustomer());
        self::assertSame(3, $statistic[1]->getCount());
        self::assertSame(200, $statistic[1]->getDuration());
        self::assertSame(2.5, $statistic[1]->getRate());
    }

    public function testGetStatisticPassesOptionsAndIsCached(): void
    {
        $options = ['foo' => 'bar'];

        $result = $this->createMock(ExportableResultInterface::class);
        $result->expects($this->once())->method('getStatistic')->with($options)->willReturn([
            new ExportableResultStatistic($this->createCustomer(1), 1, 100, 1.5),
        ]);

        $sut = new ExportableResult([$result]);

        self::assertSame($sut->getStatistic($options), $sut->getStatistic($options));
    }
}
