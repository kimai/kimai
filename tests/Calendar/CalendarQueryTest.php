<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\CalendarQuery;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CalendarQuery::class)]
class CalendarQueryTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new CalendarQuery();

        self::assertNull($sut->getDate());
        self::assertNull($sut->getUser());
        self::assertEquals('month', $sut->getView());

        $user = new User();
        $sut->setUser($user);
        self::assertSame($user, $sut->getUser());

        $date = new \DateTimeImmutable('2025-08-13 12:13:14');
        $sut->setDate($date);
        self::assertNotNull($sut->getDate());
        self::assertEquals('2025-08-13 12:13:14', $sut->getDate()->format('Y-m-d H:i:s'));

        $sut->setView('foo');
        self::assertEquals('month', $sut->getView());
    }

    #[DataProvider('getTestData')]
    public function testSetView(string $value, string $expected): void
    {
        $sut = new CalendarQuery();
        $sut->setView($value);
        self::assertEquals($expected, $sut->getView());
    }

    /**
     * @return iterable<int, array<int, string>>
     */
    public static function getTestData(): iterable
    {
        yield ['agendaMonth', 'month'];
        yield ['agendaWeek', 'week'];
        yield ['agendaDay', 'day'];
        yield ['month', 'month'];
        yield ['week', 'week'];
        yield ['day', 'day'];
        yield ['foo', 'month'];
    }
}
