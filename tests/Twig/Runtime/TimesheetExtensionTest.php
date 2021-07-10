<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Twig\Runtime\TimesheetExtension;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Twig\Runtime\TimesheetExtension
 */
class TimesheetExtensionTest extends TestCase
{
    public function testGetExporter()
    {
        $entries = [new Timesheet(), new Timesheet()];

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getActiveEntries')->willReturn($entries);

        $sut = new TimesheetExtension($repository);
        self::assertEquals($entries, $sut->activeEntries(new User()));
    }
}
