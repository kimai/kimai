<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Export\TimesheetExportRepository;
use App\Repository\CustomerRepository;
use App\Repository\TimesheetRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimesheetExportRepository::class)]
class TimesheetExportRepositoryTest extends TestCase
{
    public function testSetExported(): void
    {
        $timesheetRepository = $this->createMock(TimesheetRepository::class);
        $timesheetRepository->expects($this->once())->method('setExported')->willReturnCallback(function (array $items): void {
            self::assertCount(2, $items);
        });

        $customerRepository = $this->createMock(CustomerRepository::class);

        $sut = new TimesheetExportRepository($timesheetRepository, $customerRepository);

        $sut->setExported([new Timesheet(), null, new \stdClass(), new Timesheet(), new Activity()]);
        // test else for empty array
        /* @phpstan-ignore argument.type */
        $sut->setExported([new Customer('foo'), new Project()]);
    }

    public function testSetType(): void
    {
        $sut = new TimesheetExportRepository(
            $this->createMock(TimesheetRepository::class),
            $this->createMock(CustomerRepository::class)
        );
        self::assertEquals('timesheet', $sut->getType());
    }
}
