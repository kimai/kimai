<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Auth\User;

use App\Entity\User;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class DoctrineUserProvider implements UserProviderInterface
{
    /**
     * @var UserManagerInterface
     */
    private $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        /** @var User $user */
        $user = $this->userManager->findUserByUsernameOrEmail($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        // this needs improvements: should not check for LDAP user, but we have to ... as LDAP users cannot
        // be clearly identified by now, because the auth column was introduced after LDAP!
        if (!$user->isInternalUser() && !$user->isLdapUser()) {
            throw new UsernameNotFoundException(sprintf('User "%s" is registered, but not as internal user.', $username));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(SecurityUserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Expected an instance of %s, but got "%s".', User::class, get_class($user)));
        }

        /** @var User $reloadedUser */
        $reloadedUser = $this->userManager->findUserBy(['id' => $user->getId()]);

        if (null === $reloadedUser) {
            throw new UsernameNotFoundException(sprintf('User with ID "%s" could not be reloaded.', $user->getId()));
        }

        // this needs improvements, should not check for LDAP user!
        if (!$reloadedUser->isInternalUser() && !$reloadedUser->isLdapUser()) {
            throw new UnsupportedUserException(sprintf('User "%s" is registered, but not as internal user.', $reloadedUser->getUsername()));
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === User::class || $class === 'App\Entity\User';
    }
}
