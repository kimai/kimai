<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Calendar\DragAndDropSource;
use App\Entity\User;
use App\Event\CalendarDragAndDropSourceEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\CalendarDragAndDropSourceEvent
 */
class CalendarDragAndDropSourceEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new CalendarDragAndDropSourceEvent($user);

        self::assertSame($user, $sut->getUser());
        self::assertIsArray($sut->getSources());
        self::assertEmpty($sut->getSources());
        self::assertInstanceOf(CalendarDragAndDropSourceEvent::class, $sut->addSource(new TestDragAndDropSource()));
        self::assertCount(1, $sut->getSources());
    }
}

class TestDragAndDropSource implements DragAndDropSource
{
    public function getTitle(): string
    {
        return '';
    }

    public function getRoute(): string
    {
        return '';
    }

    public function getRouteParams(): array
    {
        return [];
    }

    public function getRouteReplacer(): array
    {
        return [];
    }

    public function getMethod(): string
    {
        return '';
    }

    public function getEntries(): array
    {
        return [];
    }

    public function getBlockInclude(): ?string
    {
        return null;
    }
}
