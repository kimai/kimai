<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Model;

use App\Entity\User;
use App\Entity\WorkingTime;
use App\WorkingTime\Model\Year;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Model\Year
 */
class YearTest extends TestCase
{
    public function testDefaults(): void
    {
        $year = new \DateTimeImmutable('2020-02-02');
        $user = new User();
        $user->setUserIdentifier('foooo');
        $sut = new Year($year, $user);

        $until = new \DateTimeImmutable('2020-09-25');

        self::assertSame($user, $sut->getUser());
        self::assertEquals(0, $sut->getActualTime());
        self::assertEquals(0, $sut->getExpectedTime($until));
    }

    public function testWithSummary(): void
    {
        $year = new \DateTimeImmutable('2020-02-02');
        $user = new User();
        $user->setUserIdentifier('foooo');
        $sut = new Year($year, $user);

        $until = new \DateTimeImmutable('2020-09-25');

        self::assertSame($user, $sut->getUser());
        self::assertEquals(0, $sut->getActualTime());
        self::assertEquals(0, $sut->getExpectedTime($until));

        $workingTime = new WorkingTime(new User(), new \DateTimeImmutable('2020-04-14'));
        $workingTime->setExpectedTime(54321);
        $workingTime->setActualTime(12345);
        $day = $sut->getMonth(new \DateTimeImmutable('2020-04-25'))->getDays()[13];
        $day->setWorkingTime($workingTime);

        self::assertEquals(12345, $sut->getActualTime());
        self::assertEquals(54321, $sut->getExpectedTime($until));

        $workingTime = new WorkingTime(new User(), new \DateTimeImmutable('2020-06-20'));
        $workingTime->setExpectedTime(9843);
        $workingTime->setActualTime(777);
        $day = $sut->getMonth(new \DateTimeImmutable('2020-06-25'))->getDays()[19];
        $day->setWorkingTime($workingTime);

        self::assertEquals(13122, $sut->getActualTime());
        self::assertEquals(64164, $sut->getExpectedTime($until));

        self::assertEquals(54321, $sut->getExpectedTime(new \DateTimeImmutable('2020-04-25')));
        self::assertEquals(0, $sut->getExpectedTime(new \DateTimeImmutable('2020-03-25')));
    }
}
