<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\AbstractActionsSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @covers \App\EventSubscriber\Actions\AbstractActionsSubscriber
 */
abstract class AbstractActionsSubscriberTest extends TestCase
{
    protected function createSubscriber(string $className, ...$grants): AbstractActionsSubscriber
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnOnConsecutiveCalls(...$grants);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnArgument(0);

        return new $className($auth, $router);
    }

    public function assertGetSubscribedEvent(string $className, string $name): void
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
