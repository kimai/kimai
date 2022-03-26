<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\UserDetailsSubscriber;
use KevinPapst\TablerBundle\Event\UserDetailsEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\EventSubscriber\UserDetailsSubscriber
 */
class UserDetailsSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $events = UserDetailsSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(UserDetailsEvent::class, $events);
        $methodName = $events[UserDetailsEvent::class][0];
        $this->assertTrue(method_exists(UserDetailsSubscriber::class, $methodName));
    }
}
