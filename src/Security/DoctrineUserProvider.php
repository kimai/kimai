<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class DoctrineUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = null;

        try {
            /** @var User|null $user */
            $user = $this->repository->loadUserByUsername($identifier);
        } catch (\Exception $ex) {
        }

        if (null === $user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Expected an instance of %s, but got "%s".', User::class, \get_class($user)));
        }

        /** @var User|null $reloadedUser */
        $reloadedUser = $this->repository->getUserById($user->getId());

        if (null === $reloadedUser) {
            throw new UserNotFoundException(sprintf('User with ID "%s" could not be reloaded.', $user->getId()));
        }

        return $reloadedUser;
    }

    public function supportsClass($class): bool
    {
        return $class === User::class;
    }

    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if ($user instanceof User) {
            try {
                $user->setPassword($newHashedPassword);
                $this->repository->saveUser($user);
            } catch (Exception $e) {
            }
        }
    }
}
