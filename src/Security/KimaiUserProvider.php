<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Configuration\SystemConfiguration;
use App\Ldap\LdapUserProvider;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KimaiUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private $providers;
    private $provider;
    private $configuration;

    /**
     * @param iterable|UserProviderInterface[] $providers
     */
    public function __construct(iterable $providers, SystemConfiguration $configuration)
    {
        $this->providers = $providers;
        $this->configuration = $configuration;
    }

    private function getInternalProvider(): ChainUserProvider
    {
        if ($this->provider === null) {
            $activated = [];
            foreach ($this->providers as $provider) {
                if ($provider instanceof LdapUserProvider) {
                    if (!$this->configuration->isLdapActive()) {
                        continue;
                    }
                }
                $activated[] = $provider;
            }
            $this->provider = new ChainUserProvider(new \ArrayIterator($activated));
        }

        return $this->provider;
    }

    /**
     * @return array
     */
    public function getProviders()
    {
        return $this->getInternalProvider()->getProviders();
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        return $this->getInternalProvider()->loadUserByUsername($username);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        return $this->getInternalProvider()->refreshUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $this->getInternalProvider()->supportsClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        $this->getInternalProvider()->upgradePassword($user, $newEncodedPassword);
    }
}
