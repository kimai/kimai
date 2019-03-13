<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use App\Kernel;
use App\Plugin\PluginManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PluginManagerCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has(PluginManager::class)) {
            return;
        }

        $definition = $container->findDefinition(PluginManager::class);

        $taggedRenderer = $container->findTaggedServiceIds(Kernel::TAG_PLUGIN);
        foreach ($taggedRenderer as $id => $tags) {
            $definition->addMethodCall('addPlugin', [new Reference($id)]);
        }

        $taggedRenderer = $container->findTaggedServiceIds('kimai.bundle');
        foreach ($taggedRenderer as $id => $tags) {
            $definition->addMethodCall('addPlugin', [new Reference($id)]);
        }
    }
}
