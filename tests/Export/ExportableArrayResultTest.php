<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Export\ExportableArrayResult;
use App\Export\ExportableResultInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExportableArrayResult::class)]
class ExportableArrayResultTest extends TestCase
{
    private function createCustomer(int $id): Customer
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn($id);

        return $customer;
    }

    private function createTimesheet(Customer $customer, ?int $duration, float $rate): Timesheet
    {
        $project = new Project();
        $project->setCustomer($customer);

        $timesheet = new Timesheet();
        $timesheet->setProject($project);
        $timesheet->setDuration($duration);
        $timesheet->setRate($rate);

        return $timesheet;
    }

    public function testEmpty(): void
    {
        $sut = new ExportableArrayResult([]);

        self::assertInstanceOf(ExportableResultInterface::class, $sut);
        self::assertSame([], $sut->getExportItems());
        self::assertSame([], $sut->getStatistic());
    }

    public function testGetExportItems(): void
    {
        $items = [
            $this->createTimesheet($this->createCustomer(1), 100, 1.0),
            $this->createTimesheet($this->createCustomer(2), 200, 2.0),
        ];

        $sut = new ExportableArrayResult($items);

        self::assertSame($items, $sut->getExportItems());
    }

    public function testGetStatisticGroupsByCustomer(): void
    {
        $customer1 = $this->createCustomer(1);
        $customer2 = $this->createCustomer(2);

        $sut = new ExportableArrayResult([
            $this->createTimesheet($customer1, 100, 1.5),
            $this->createTimesheet($customer2, 200, 2.5),
            $this->createTimesheet($customer1, 300, 3.5),
            // running records do not have a duration
            $this->createTimesheet($customer1, null, 0.5),
        ]);

        $statistic = $sut->getStatistic();

        self::assertCount(2, $statistic);

        self::assertSame($customer1, $statistic[0]->getCustomer());
        self::assertSame(3, $statistic[0]->getCount());
        self::assertSame(400, $statistic[0]->getDuration());
        self::assertSame(5.5, $statistic[0]->getRate());

        self::assertSame($customer2, $statistic[1]->getCustomer());
        self::assertSame(1, $statistic[1]->getCount());
        self::assertSame(200, $statistic[1]->getDuration());
        self::assertSame(2.5, $statistic[1]->getRate());
    }

    public function testGetStatisticIsCached(): void
    {
        $sut = new ExportableArrayResult([$this->createTimesheet($this->createCustomer(1), 100, 1.5)]);

        self::assertSame($sut->getStatistic(), $sut->getStatistic());
    }

    public function testGetStatisticThrowsOnMissingProject(): void
    {
        $sut = new ExportableArrayResult([new Timesheet()]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Found export item with empty project or customer');

        $sut->getStatistic();
    }

    public function testGetStatisticThrowsOnMissingCustomer(): void
    {
        $timesheet = new Timesheet();
        $timesheet->setProject(new Project());

        $sut = new ExportableArrayResult([$timesheet]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Found export item with empty project or customer');

        $sut->getStatistic();
    }
}
