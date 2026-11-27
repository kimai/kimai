<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\WorkingTimeYearsEvent;
use App\WorkingTime\Model\Year;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkingTimeYearsEvent::class)]
class WorkingTimeYearsEventTest extends TestCase
{
    public function testGetter(): void
    {
        $date = new \DateTime('2023-02-10');
        $year1 = new Year($date, new User());
        $year2 = new Year($date, new User());
        $sut = new WorkingTimeYearsEvent([$year1, $year2], clone $date);

        self::assertSame([$year1, $year2], $sut->getYears());
        self::assertEquals('2023-02-10', $sut->getUntil()->format('Y-m-d'));
    }

    public function testEmpty(): void
    {
        $sut = new WorkingTimeYearsEvent([], new \DateTime('2023-02-10'));

        self::assertSame([], $sut->getYears());
    }
}
