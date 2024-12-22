<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\CalendarSource;
use App\Calendar\CalendarSourceType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Calendar\CalendarSource
 */
class CalendarSourceTest extends TestCase
{
    public function testSource(): void
    {
        $sut = new CalendarSource(CalendarSourceType::JSON, '0815', 'askdjfhlaksjdhflaksjhdflkjasdlkfjh', '#fffccc');
        $sut->addOption('foo', 'bar');
        $sut->addOption('hello', false);
        $sut->addOption('world', 123);

        self::assertEquals(CalendarSourceType::JSON, $sut->getType());
        self::assertEquals('json', $sut->getTypeName());
        self::assertEquals('0815', $sut->getId());
        self::assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
        self::assertEquals('#fffccc', $sut->getColor());
        self::assertEquals(['foo' => 'bar', 'hello' => false, 'world' => 123], $sut->getOptions());

        $sut = new CalendarSource(CalendarSourceType::TIMESHEET, '0815', 'askdjfhlaksjdhflaksjhdflkjasdlkfjh', null);

        self::assertEquals('0815', $sut->getId());
        self::assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
        self::assertNull($sut->getColor());
    }
}
