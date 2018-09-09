<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Dynamically adds twig globals.
 */
class TwigContextCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $twig = $container->getDefinition('twig');
        $theme = $container->getParameter('kimai.theme');
        $durationOnly = $container->getParameter('kimai.timesheet.duration_only');

        $twig->addMethodCall('addGlobal', ['kimai_context', $theme]);
        $twig->addMethodCall('addGlobal', ['duration_only', $durationOnly]);

        if ($container->hasDefinition('twig.loader.native_filesystem')) {
            $definition = $container->getDefinition('twig.loader.native_filesystem');

            $path = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR;
            foreach ($container->getParameter('kimai.invoice.documents') as $invoicePath) {
                if (!is_dir($path . $invoicePath)) {
                    continue;
                }
                $definition->addMethodCall('addPath', [$path . $invoicePath, 'invoice']);
            }
        }
    }
}
