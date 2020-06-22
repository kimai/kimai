<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Inspired by https://github.com/Maks3w/FR3DLdapBundle @ MIT License
 */
class FormLoginLdapFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPoint)
    {
        $authProviderId = $this->createAuthProvider($container, $id, $userProviderId);
        $listenerId = $this->createListener($container, $id, $config);

        return [$authProviderId, $listenerId, $defaultEntryPoint];
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'kimai_ldap';
    }

    public function addConfiguration(NodeDefinition $node)
    {
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $userProviderId)
    {
        $providerId = 'security.authentication.provider.kimai_ldap.' . $id;

        $container
            ->setDefinition($providerId, new ChildDefinition(LdapAuthenticationProvider::class))
            ->replaceArgument(1, $id)
            ->replaceArgument(2, new Reference($userProviderId))
        ;

        return $providerId;
    }

    protected function createListener(ContainerBuilder $container, $id, $config)
    {
        $listener = 'security.authentication.listener.form';
        $listenerId = $listener . '.' . $id;

        $container
            ->setDefinition($listenerId, new ChildDefinition($listener))
            ->replaceArgument(4, $id)
            ->replaceArgument(5, $config)
        ;

        return $listenerId;
    }
}
