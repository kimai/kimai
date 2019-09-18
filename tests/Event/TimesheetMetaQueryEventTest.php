<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\TimesheetMeta;
use App\Event\MetaQueryEventInterface;
use App\Event\TimesheetMetaQueryEvent;
use App\Repository\Query\TimesheetQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\TimesheetMetaQueryEvent
 */
class TimesheetMetaQueryEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $query = new TimesheetQuery();
        $sut = new TimesheetMetaQueryEvent($query, TimesheetMetaQueryEvent::EXPORT);

        self::assertInstanceOf(MetaQueryEventInterface::class, $sut);
        self::assertSame($sut->getQuery(), $query);
        self::assertIsArray($sut->getFields());
        self::assertEmpty($sut->getFields());
        self::assertEquals('export', $sut->getLocation());

        $sut->addField(new TimesheetMeta());
        $sut->addField(new TimesheetMeta());

        self::assertCount(2, $sut->getFields());
    }
}
