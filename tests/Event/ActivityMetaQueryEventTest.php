<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\ActivityMeta;
use App\Event\ActivityMetaQueryEvent;
use App\Event\MetaQueryEventInterface;
use App\Repository\Query\ActivityQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ActivityMetaQueryEvent
 */
class ActivityMetaQueryEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $query = new ActivityQuery();
        $sut = new ActivityMetaQueryEvent($query, ActivityMetaQueryEvent::EXPORT);

        self::assertInstanceOf(MetaQueryEventInterface::class, $sut);
        self::assertSame($sut->getQuery(), $query);
        self::assertIsArray($sut->getFields());
        self::assertEmpty($sut->getFields());
        self::assertEquals('export', $sut->getLocation());

        $sut->addField(new ActivityMeta());
        $sut->addField(new ActivityMeta());

        self::assertCount(2, $sut->getFields());
    }
}
