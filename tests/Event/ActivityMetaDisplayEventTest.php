<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\ActivityMeta;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Repository\Query\ActivityQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ActivityMetaDisplayEvent
 */
class ActivityMetaDisplayEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $query = new ActivityQuery();
        $sut = new ActivityMetaDisplayEvent($query, ActivityMetaDisplayEvent::EXPORT);

        self::assertInstanceOf(MetaDisplayEventInterface::class, $sut);
        self::assertSame($sut->getQuery(), $query);
        self::assertIsArray($sut->getFields());
        self::assertEmpty($sut->getFields());
        self::assertEquals('export', $sut->getLocation());

        $sut->addField(new ActivityMeta());
        $sut->addField(new ActivityMeta());

        self::assertCount(2, $sut->getFields());
    }
}
