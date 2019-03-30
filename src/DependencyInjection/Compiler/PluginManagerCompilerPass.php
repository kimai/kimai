<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use App\Kernel;
use App\License\LicenseManager;
use App\Plugin\PluginInterface;
use App\Plugin\PluginManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;

class PluginManagerCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(PluginManager::class)) {
            return;
        }

        $pluginManager = $container->findDefinition(PluginManager::class);
        $plugins = $container->findTaggedServiceIds(Kernel::TAG_PLUGIN);

        foreach ($plugins as $id => $tags) {
            if ($this->validateBundle($id)) {
                $pluginManager->addMethodCall('addPlugin', [new Reference($id)]);
            }
        }
    }

    /**
     * @param string $id
     * @return bool
     * @throws \ReflectionException
     */
    protected function validateBundle($id)
    {
        if (substr($id, 0, 12) !== 'KimaiPlugin\\') {
            throw new RuntimeException('Invalid Kimai bundle, namespace must start with "KimaiPlugin\\"');
        }

        $class = new \ReflectionClass($id);

        $path = dirname($class->getFileName());

        if (!$class->implementsInterface(PluginInterface::class)) {
            throw new RuntimeException('Invalid or outdated Kimai bundle, update or remove it: ' . $path);
        }

        return true;
    }
}
