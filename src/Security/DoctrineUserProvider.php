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
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class DoctrineUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /**
     * @var UserRepository
     */
    private $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $user = null;

        try {
            /** @var User|null $user */
            $user = $this->repository->loadUserByUsername($username);
        } catch (\Exception $ex) {
        }

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Expected an instance of %s, but got "%s".', User::class, \get_class($user)));
        }

        /** @var User|null $reloadedUser */
        $reloadedUser = $this->repository->getUserById($user->getId());

        if (null === $reloadedUser) {
            throw new UsernameNotFoundException(sprintf('User with ID "%s" could not be reloaded.', $user->getId()));
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === User::class;
    }

    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if ($user instanceof User) {
            try {
                $user->setPassword($newEncodedPassword);
                $this->repository->saveUser($user);
            } catch (Exception $e) {
            }
        }
    }
}
