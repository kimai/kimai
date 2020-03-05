<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use App\Kernel;
use App\Repository\WidgetRepository;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Dynamically adds all widgets to the WidgetRepository.
 */
class WidgetCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(WidgetRepository::class);

        $taggedRenderer = $container->findTaggedServiceIds(Kernel::TAG_WIDGET);
        foreach ($taggedRenderer as $id => $tags) {
            $definition->addMethodCall('registerWidget', [new Reference($id)]);
        }
    }
}
