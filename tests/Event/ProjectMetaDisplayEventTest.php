<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\ProjectMeta;
use App\Event\AbstractMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Repository\Query\ProjectQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractMetaDisplayEvent::class)]
#[CoversClass(ProjectMetaDisplayEvent::class)]
class ProjectMetaDisplayEventTest extends TestCase
{
    public function testGetterAndSetter(): void
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
