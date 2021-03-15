<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use PHPUnit\Framework\TestCase;

/**
 * @covers \App\EventSubscriber\Actions\AbstractActionsSubscriber
 */
abstract class AbstractActionsSubscriberTest extends TestCase
{
    protected function assertGetSubscribedEvent(string $className, string $name)
    {
        $this->assertTrue(method_exists($className, 'getSubscribedEvents'));
        $events = $className::getSubscribedEvents();
        $actionName = array_keys($events)[0];
        $config = $events[$actionName];
        $this->assertEquals('actions.' . $name, $actionName);
        $this->assertTrue(method_exists($className, $config[0]));
        $this->assertEquals(1000, $config[1]);
    }
}
