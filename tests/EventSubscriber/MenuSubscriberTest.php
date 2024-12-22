<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\EventSubscriber\MenuSubscriber;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\EventSubscriber\MenuSubscriber
 */
class MenuSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = MenuSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(ConfigureMainMenuEvent::class, $events);
        $methodName = $events[ConfigureMainMenuEvent::class][0];
        self::assertTrue(method_exists(MenuSubscriber::class, $methodName));
    }
}
