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
    public function testGetterAndSetter(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new CalendarDragAndDropSourceEvent($user, 10);

        $hello = new TestDragAndDropSource('hello');
        $tmp1 = new TestDragAndDropSource('foo');
        $tmp2 = new TestDragAndDropSource('bar');
        $tmp3 = new TestDragAndDropSource('hello');

        self::assertSame($user, $sut->getUser());
        self::assertIsArray($sut->getSources());
        self::assertEmpty($sut->getSources());
        self::assertInstanceOf(CalendarDragAndDropSourceEvent::class, $sut->addSource($tmp1));
        self::assertInstanceOf(CalendarDragAndDropSourceEvent::class, $sut->addSource($tmp2));
        self::assertInstanceOf(CalendarDragAndDropSourceEvent::class, $sut->addSource($hello));
        self::assertInstanceOf(CalendarDragAndDropSourceEvent::class, $sut->addSource($tmp3));
        self::assertCount(4, $sut->getSources());
        self::assertEquals([$tmp1, $tmp2, $hello, $tmp3], $sut->getSources());
        self::assertEquals(10, $sut->getMaxEntries());

        self::assertFalse($sut->removeSource(new TestDragAndDropSource('foo')));
        self::assertTrue($sut->removeSource($hello));
        self::assertFalse($sut->removeSource(new TestDragAndDropSource('world')));
        self::assertCount(3, $sut->getSources());
        self::assertEquals([$tmp1, $tmp2, $tmp3], $sut->getSources());
    }
}

class TestDragAndDropSource implements DragAndDropSource
{
    private $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTranslationDomain(): string
    {
        return 'messages';
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
