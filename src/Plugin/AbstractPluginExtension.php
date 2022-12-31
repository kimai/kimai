<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

abstract class AbstractPluginExtension extends Extension
{
    protected function registerBundleConfiguration(ContainerBuilder $container, array $configs): void
    {
        $bundleConfig = [$this->getAlias() => $configs];

        if ($container->hasParameter('kimai.bundles.config')) { // @phpstan-ignore-line
            $config = $container->getParameter('kimai.bundles.config');
            if (!\is_array($config)) {
                throw new \Exception('Invalid bundle configuration registered for ' . $this->getAlias());
            }
            $bundleConfig = array_merge($config, $bundleConfig);
        }

        $container->setParameter('kimai.bundles.config', $bundleConfig);
    }
}
