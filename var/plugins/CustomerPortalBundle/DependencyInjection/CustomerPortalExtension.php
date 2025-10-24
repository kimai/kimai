<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class CustomerPortalExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('kimai', [
            'permissions' => [
                'roles' => [
                    'ROLE_SUPER_ADMIN' => [
                        'customer_portal',
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('security', [
            'password_hashers' => [
                'customer_portal' => 'auto',
            ],
        ]);

        $container->prependExtensionConfig('framework', [
            'rate_limiter' => [
                'customer_portal' => [
                    'policy' => 'fixed_window',
                    'limit' => 10,
                    'interval' => '1 hour',
                    'lock_factory' => null,
                ],
            ],
        ]);
    }
}
