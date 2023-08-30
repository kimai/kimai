<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\CalendarSourceEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\CalendarSourceEvent
 */
class CalendarSourceEventTest extends TestCase
{
    public function testEvent(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new CalendarSourceEvent($user);

        self::assertSame($user, $sut->getUser());
        self::assertIsArray($sut->getSources());
        self::assertEmpty($sut->getSources());
        self::assertCount(1, $sut->getSources());
    }
}
