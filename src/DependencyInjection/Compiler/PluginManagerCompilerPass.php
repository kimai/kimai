<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

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

        if (!$container->has(LicenseManager::class)) {
            throw new RuntimeException('Missing LicenseManager, cannot build container');
        }

        $manager = $container->get(LicenseManager::class);
        foreach ($manager->getPluginLicenses() as $license) {
            $pluginManager->addMethodCall('addLicense', [$license->toArray()]);
        }

        $bundles = $container->getParameter('kernel.bundles');
        $taggedBundles = [];
        foreach ($bundles as $id => $className) {
            if (substr($className, 0, 12) === 'KimaiPlugin\\') {
                $taggedBundles[] = $className;
            }
        }
        foreach ($taggedBundles as $id) {
            $this->validateBundle($id);
            // a fallback if the Bundle itself was not registered as service
            if (!$container->has($id)) {
                $container->register($id, $id);
            }
            $pluginManager->addMethodCall('addPlugin', [new Reference($id)]);
        }
    }

    /**
     * @param $id
     * @throws \ReflectionException
     * @throws RuntimeException
     */
    protected function validateBundle($id)
    {
        $class = new \ReflectionClass($id);

        $path = dirname($class->getFileName());

        if (!$class->implementsInterface(PluginInterface::class)) {
            throw new RuntimeException('Invalid or outdated Kimai bundle, update or remove it: ' . $path);
        }

        $composerFile = $path . '/composer.json';
        if (!file_exists($composerFile) || !is_readable($composerFile)) {
            throw new RuntimeException('Missing composer.json in ' . $path);
        }

        $composer = md5_file($composerFile);

        $bundle = $class->newInstance();
        $method = $class->getMethod('getChecksum');
        $checksum = $method->invoke($bundle);

        if ($composer !== $checksum) {
            throw new RuntimeException('Manipulated bundle found in ' . $path);
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        $method = $class->getMethod('getLicenseRequirements');
        $allowed = $method->invoke($bundle);

        if (!isset($composer['extra']) || !isset($composer['extra']['kimai']) || !isset($composer['extra']['kimai']['license'])) {
            throw new RuntimeException('Missing license information in composer.json at ' . $path);
        }

        foreach ($composer['extra']['kimai']['license'] as $licenseStatus) {
            if (!in_array($licenseStatus, $allowed)) {
                throw new RuntimeException('License requirement mismatch in ' . $path);
            }
        }

        foreach ($allowed as $licenseStatus) {
            if (!in_array($licenseStatus, $composer['extra']['kimai']['license'])) {
                throw new RuntimeException('License requirement mismatch in ' . $path);
            }
        }

        if (!isset($composer['extra']['kimai']['version'])) {
            throw new RuntimeException('Incomplete composer data, missing extra.kimai.version in ' . $path);
        }

        if (!isset($composer['extra']['kimai']['require'])) {
            throw new RuntimeException('Incomplete composer data, missing extra.kimai.require in ' . $path);
        }
    }
}
