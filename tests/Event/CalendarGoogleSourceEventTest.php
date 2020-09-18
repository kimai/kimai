<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Calendar\GoogleSource;
use App\Entity\User;
use App\Event\CalendarGoogleSourceEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\CalendarGoogleSourceEvent
 */
class CalendarGoogleSourceEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new CalendarGoogleSourceEvent($user);

        self::assertSame($user, $sut->getUser());
        self::assertIsArray($sut->getSources());
        self::assertEmpty($sut->getSources());
        self::assertInstanceOf(CalendarGoogleSourceEvent::class, $sut->addSource(new GoogleSource('', '')));
        self::assertCount(1, $sut->getSources());
    }
}
