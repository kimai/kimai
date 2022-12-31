<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\EventSubscriber\MenuFavoritesSubscriber;
use App\EventSubscriber\MenuSubscriber;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\EventSubscriber\MenuFavoritesSubscriber
 */
class MenuFavoritesSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = MenuFavoritesSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(ConfigureMainMenuEvent::class, $events);
        $methodName = $events[ConfigureMainMenuEvent::class][0];
        self::assertIsString($methodName);
        $this->assertTrue(method_exists(MenuSubscriber::class, $methodName));
    }
}
