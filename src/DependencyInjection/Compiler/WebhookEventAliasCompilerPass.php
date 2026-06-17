<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use App\Webhook\WebhookListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class WebhookEventAliasCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $dispatcher = $container->getDefinition('event_dispatcher');
        $listener = $container->getDefinition(WebhookListener::class);

        foreach($container->findTaggedResourceIds('webhook.event') as $id => $tags) {
            $c = $container->getDefinition($id)->getClass();
            $dispatcher->addMethodCall('addListener', [$c, [$listener, 'triggerWebhook'], 0]);
        }
    }
}
