<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\WebhookCompilerPass;
use App\Event\ActivityCreatePostEvent;
use App\Event\CustomerUpdatePostEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(WebhookCompilerPass::class)]
class WebhookCompilerPassTest extends TestCase
{
    public function testParameterIsSetWithTaggedClasses(): void
    {
        $container = new ContainerBuilder();

        $events = [ActivityCreatePostEvent::class, CustomerUpdatePostEvent::class];
        foreach ($events as $event) {
            $container->register($event, $event)->addResourceTag('webhook.event');
        }

        $sut = new WebhookCompilerPass();
        $sut->process($container);

        self::assertTrue($container->hasParameter('kimai.webhook_events'));
        $parameter = $container->getParameter('kimai.webhook_events');
        self::assertIsArray($parameter);
        self::assertCount(2, $parameter);
        self::assertContains(ActivityCreatePostEvent::class, $parameter);
        self::assertContains(CustomerUpdatePostEvent::class, $parameter);
    }

    public function testParameterIsEmptyArrayWithoutTaggedServices(): void
    {
        $container = new ContainerBuilder();

        $sut = new WebhookCompilerPass();
        $sut->process($container);

        self::assertTrue($container->hasParameter('kimai.webhook_events'));
        self::assertSame([], $container->getParameter('kimai.webhook_events'));
    }
}
