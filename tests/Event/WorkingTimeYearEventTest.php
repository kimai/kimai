<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\WorkingTimeYearEvent;
use App\WorkingTime\Model\Year;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\WorkingTimeYearEvent
 */
class WorkingTimeYearEventTest extends TestCase
{
    public function testGetter(): void
    {
        $user = new User();
        $date = new \DateTime('2023-02-10');
        $year = new Year($date, $user);
        $sut = new WorkingTimeYearEvent($year, clone $date);

        self::assertSame($year, $sut->getYear());
        self::assertEquals('2023-02-10', $sut->getUntil()->format('Y-m-d'));
    }
}
