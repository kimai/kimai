<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\WebhookEventAliasCompilerPass;
use App\Event\ActivityCreatePostEvent;
use App\Event\CustomerUpdatePostEvent;
use App\Webhook\WebhookListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(WebhookEventAliasCompilerPass::class)]
class WebhookEventAliasCompilerPassTest extends TestCase
{
    private function getContainer(array $events = []): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setDefinition('event_dispatcher', new Definition('event_dispatcher'));
        $container->setDefinition(WebhookListener::class, new Definition(WebhookListener::class));

        foreach ($events as $event) {
            $container->register($event, $event)->addResourceTag('webhook.event');
        }

        return $container;
    }

    public function testListenerCallsAreAdded(): void
    {
        $container = $this->getContainer([ActivityCreatePostEvent::class, CustomerUpdatePostEvent::class]);

        $sut = new WebhookEventAliasCompilerPass();
        $sut->process($container);

        $dispatcher = $container->findDefinition('event_dispatcher');
        $methods = $dispatcher->getMethodCalls();

        self::assertCount(2, $methods);
        self::assertTrue($dispatcher->hasMethodCall('addListener'));

        $listener = $container->findDefinition(WebhookListener::class);
        $eventClasses = [];
        foreach ($methods as $methodCall) {
            self::assertSame('addListener', $methodCall[0]);
            $arguments = $methodCall[1];
            self::assertCount(3, $arguments);
            $eventClasses[] = $arguments[0];
            self::assertSame([$listener, 'triggerWebhook'], $arguments[1]);
            self::assertSame(0, $arguments[2]);
        }

        self::assertContains(ActivityCreatePostEvent::class, $eventClasses);
        self::assertContains(CustomerUpdatePostEvent::class, $eventClasses);
    }

    public function testNoCallsWithoutTaggedEvents(): void
    {
        $container = $this->getContainer();

        $sut = new WebhookEventAliasCompilerPass();
        $sut->process($container);

        $dispatcher = $container->findDefinition('event_dispatcher');
        self::assertCount(0, $dispatcher->getMethodCalls());
    }
}
