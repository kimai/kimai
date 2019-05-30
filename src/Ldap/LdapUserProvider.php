<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Own implementation, to be able to deactivate LDAP via config switch.
 */
class LdapUserProvider implements UserProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    protected $ldap;
    /**
     * @var bool
     */
    protected $activated = false;

    public function __construct(UserProviderInterface $ldap, bool $activated)
    {
        $this->ldap = $ldap;
        $this->activated = $activated;
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    public function loadUserByUsername($username)
    {
        if (!$this->activated) {
            $ex = new UsernameNotFoundException(sprintf('User "%s" not found', $username));
            $ex->setUsername($username);

            throw $ex;
        }

        return $this->ldap->loadUserByUsername($username);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        if (!$this->activated) {
            return false;
        }

        return $this->ldap->supportsClass($class);
    }
}
