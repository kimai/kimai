<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Repository\Query\InvoiceQuery;
use App\Repository\TimesheetInvoiceItemRepository;
use App\Repository\TimesheetRepository;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\TimesheetInvoiceItemRepository
 */
class TimesheetInvoiceItemRepositoryTest extends TestCase
{
    public function testSaveInvoice()
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->expects($this->once())->method('saveMultiple')->willReturnCallback(function (array $items) {
            self::assertCount(2, $items);
            /** @var Timesheet $timesheet */
            foreach ($items as $timesheet) {
                self::assertInstanceOf(Timesheet::class, $timesheet);
                self::assertTrue($timesheet->isExported());
                self::assertTrue($timesheet->isInvoiced());
                self::assertCount(1, $timesheet->getInvoices());
            }
        });

        $sut = new TimesheetInvoiceItemRepository($repository);

        $query = new InvoiceQuery();
        $query->setMarkAsExported(true);

        $invoice = new Invoice();
        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter());
        /* @phpstan-ignore-next-line */
        $model->addEntries([new Timesheet(), null, new \stdClass(), new Timesheet(), new Activity()]);
        $model->setQuery($query);
        $sut->saveInvoice($invoice, $model);

        // test else for empty array
        $invoice = new Invoice();
        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter());
        /* @phpstan-ignore-next-line */
        $model->addEntries([new Customer(), new Project()]);
        $model->setQuery($query);
        $sut->saveInvoice($invoice, $model);
    }
}
