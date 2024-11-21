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
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Repository\TimesheetInvoiceItemRepository;
use App\Repository\TimesheetRepository;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\TimesheetInvoiceItemRepository
 */
class TimesheetInvoiceItemRepositoryTest extends TestCase
{
    public function testSetExported(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->expects($this->once())->method('setExported')->willReturnCallback(function (array $items) {
            self::assertCount(2, $items);
        });

        $sut = new TimesheetInvoiceItemRepository($repository);

        $sut->setExported([new Timesheet(), null, new \stdClass(), new Timesheet(), new Activity()]);
        // test else for empty array
        /* @phpstan-ignore argument.type */
        $sut->setExported([new Customer('foo'), new Project()]);
    }
}
