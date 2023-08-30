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

        $this->assertEquals(CalendarSourceType::JSON, $sut->getType());
        $this->assertEquals('json', $sut->getTypeName());
        $this->assertEquals('0815', $sut->getId());
        $this->assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
        $this->assertEquals('#fffccc', $sut->getColor());
        $this->assertEquals(['foo' => 'bar', 'hello' => false, 'world' => 123], $sut->getOptions());

        $sut = new CalendarSource(CalendarSourceType::TIMESHEET, '0815', 'askdjfhlaksjdhflaksjhdflkjasdlkfjh', null);

        $this->assertEquals('0815', $sut->getId());
        $this->assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
        $this->assertNull($sut->getColor());
    }
}
