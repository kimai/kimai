<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Ldap\LdapUserProvider;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @template-implements PasswordUpgraderInterface<User>
 * @template-implements UserProviderInterface<User>
 */
final class KimaiUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private ?ChainUserProvider $provider = null;

    /**
     * @param iterable<UserProviderInterface<User>> $providers
     */
    public function __construct(private iterable $providers, private SystemConfiguration $configuration)
    {
    }

    private function getInternalProvider(): ChainUserProvider
    {
        if ($this->provider === null) {
            $activated = [];
            foreach ($this->providers as $provider) {
                if ($provider instanceof LdapUserProvider) {
                    if (!class_exists('Laminas\Ldap\Ldap')) {
                        continue;
                    }
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

    public function getProviders(): array
    {
        return $this->getInternalProvider()->getProviders();
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->getInternalProvider()->loadUserByIdentifier($identifier); // @phpstan-ignore-line
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->getInternalProvider()->refreshUser($user); // @phpstan-ignore-line
    }

    public function supportsClass(string $class): bool
    {
        return $this->getInternalProvider()->supportsClass($class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $this->getInternalProvider()->upgradePassword($user, $newHashedPassword);
    }
}
