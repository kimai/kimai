<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Model;

use App\Entity\User;
use App\WorkingTime\Model\Year;
use App\WorkingTime\Model\YearPerUserSummary;
use App\WorkingTime\Model\YearSummary;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Model\YearPerUserSummary
 */
class YearPerUserSummaryTest extends TestCase
{
    public function testDefaults(): void
    {
        $monthDate = new \DateTimeImmutable();
        $user = new User();
        $user->setUserIdentifier('foooo');
        $year = new Year($monthDate, $user);
        $sut = new YearPerUserSummary($year);

        self::assertSame($year, $sut->getYear());
        self::assertSame($user, $sut->getUser());
        self::assertEquals(0, $sut->getExpectedTime());
        self::assertEquals(0, $sut->getActualTime());
        self::assertEquals(0, $sut->count());
        self::assertEquals([], $sut->getSummaries());
    }

    public function testWithSummary(): void
    {
        $monthDate = new \DateTimeImmutable();
        $user = new User();
        $user->setUserIdentifier('foooo');
        $year = new Year($monthDate, $user);
        $sut = new YearPerUserSummary($year);

        $summary = new YearSummary($monthDate, 'Foo-Bar');

        $monthSummary = $summary->getMonth(new \DateTimeImmutable('2020-04-27'));
        $monthSummary->setActualTime(35679);
        $monthSummary->setExpectedTime(135679);

        $monthSummary = $summary->getMonth(new \DateTimeImmutable('2020-07-27'));
        $monthSummary->setActualTime(95679);
        $monthSummary->setExpectedTime(235679);

        $sut->addSummary($summary);

        self::assertEquals(131358, $sut->getActualTime());
        self::assertEquals(371358, $sut->getExpectedTime());
        self::assertEquals(1, $sut->count());
        self::assertEquals([$summary], $sut->getSummaries());
    }
}
