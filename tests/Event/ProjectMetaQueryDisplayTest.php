<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\ProjectMeta;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Repository\Query\ProjectQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ProjectMetaDisplayEvent
 */
class ProjectMetaQueryDisplayTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $query = new ProjectQuery();
        $sut = new ProjectMetaDisplayEvent($query, ProjectMetaDisplayEvent::EXPORT);

        self::assertInstanceOf(MetaDisplayEventInterface::class, $sut);
        self::assertSame($sut->getQuery(), $query);
        self::assertIsArray($sut->getFields());
        self::assertEmpty($sut->getFields());
        self::assertEquals('export', $sut->getLocation());

        $sut->addField(new ProjectMeta());
        $sut->addField(new ProjectMeta());

        self::assertCount(2, $sut->getFields());
    }
}
