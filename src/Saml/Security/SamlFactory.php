<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml\Security;

use App\Saml\Logout\SamlLogoutHandler;
use App\Saml\Provider\SamlProvider;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class SamlFactory extends AbstractFactory
{
    public function __construct()
    {
        $this->addOption('check_path', 'saml_acs');
        $this->addOption('failure_path', 'fos_user_security_login');
        $this->addOption('success_handler', SamlAuthenticationSuccessHandler::class);
        $this->defaultFailureHandlerOptions['login_path'] = 'saml_login';
    }

    protected function isRememberMeAware($config)
    {
        return false;
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'kimai_saml';
    }

    protected function getListenerId()
    {
        return 'kimai.saml_listener';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'security.authentication.provider.saml.' . $id;
        $definition = $container->setDefinition($providerId, new ChildDefinition(SamlProvider::class));
        $definition->replaceArgument(1, new Reference($userProviderId));

        return $providerId;
    }

    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);
        $this->createLogoutHandler($container, $id, $config);

        return $listenerId;
    }

    private function createLogoutHandler(ContainerBuilder $container, $id, $config)
    {
        if ($container->hasDefinition('security.logout_listener.' . $id)) {
            $logoutListener = $container->getDefinition('security.logout_listener.' . $id);

            $container
                ->setDefinition(SamlLogoutHandler::class, new ChildDefinition('saml.security.http.logout'))
                ->replaceArgument(2, array_intersect_key($config, $this->options));
            $logoutListener->addMethodCall('addHandler', [new Reference(SamlLogoutHandler::class)]);
        }
    }
}
