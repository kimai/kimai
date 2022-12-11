<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Configuration\LdapConfiguration;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class FormLoginLdapFactory extends AbstractFactory implements AuthenticatorFactoryInterface
{
    public function __construct()
    {
        $this->addOption('username_parameter', '_username');
        $this->addOption('password_parameter', '_password');
        $this->addOption('csrf_parameter', '_csrf_token');
        $this->addOption('csrf_token_id', 'authenticate');
        $this->addOption('enable_csrf', false);
        $this->addOption('post_only', true);
        $this->addOption('form_only', false);
    }

    public function getPriority(): int
    {
        return -20;
    }

    public function getKey(): string
    {
        return 'kimai_ldap';
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $key = $this->getKey();

        $authenticatorId = 'security.authenticator.form_login.' . $firewallName;
        $options = array_intersect_key($config, $this->options);
        $authenticator = $container
            ->setDefinition($authenticatorId, new ChildDefinition('security.authenticator.form_login'))
            ->replaceArgument(1, new Reference($userProviderId))
            ->replaceArgument(2, new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config)))
            ->replaceArgument(3, new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config)))
            ->replaceArgument(4, $options);

        if ($options['use_forward'] ?? false) {
            $authenticator->addMethodCall('setHttpKernel', [new Reference('http_kernel')]);
        }

        $container->setDefinition('security.listener.' . $key . '.' . $firewallName, new Definition(LdapCredentialsSubscriber::class))
            ->addTag('kernel.event_subscriber', ['dispatcher' => 'security.event_dispatcher.' . $firewallName])
            ->addArgument(new Reference(LdapManager::class))
        ;

        $ldapAuthenticatorId = 'security.authenticator.' . $key . '.' . $firewallName;
        $container->setDefinition($ldapAuthenticatorId, new Definition(LdapAuthenticator::class))
            ->setArguments([
                new Reference($authenticatorId),
                new Reference(LdapConfiguration::class),
            ]);

        return $ldapAuthenticatorId;
    }
}
