<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\WorkingTimeYearSummaryEvent;
use App\WorkingTime\Model\Year;
use App\WorkingTime\Model\YearPerUserSummary;
use App\WorkingTime\Model\YearSummary;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\WorkingTimeYearSummaryEvent
 */
class WorkingTimeYearSummaryEventTest extends TestCase
{
    public function testGetter(): void
    {
        $user = new User();
        $date = new \DateTime('2023-02-10');
        $year = new Year($date, $user);
        $until = new \DateTimeImmutable();
        $yearPerUser = new YearPerUserSummary($year);
        $sut = new WorkingTimeYearSummaryEvent($yearPerUser, $until);

        self::assertSame($year, $sut->getYear());
        self::assertSame($until, $sut->getUntil());

        $month = new \DateTime('2023-04-10');
        $holiday = new YearSummary($month, 'holiday');
        $sut->addSummary($holiday);
        $sickness = new YearSummary($month, 'sickness');
        $sut->addSummary($sickness);
        self::assertEquals([$holiday, $sickness], $yearPerUser->getSummaries());
    }
}
