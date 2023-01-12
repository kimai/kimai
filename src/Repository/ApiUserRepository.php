<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @template-implements PasswordUpgraderInterface<User>
 */
class ApiUserRepository implements UserLoaderInterface, PasswordUpgraderInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->userRepository->loadUserByIdentifier($identifier);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface|UserInterface $user, string $newHashedPassword): void
    {
        if (!($user instanceof User)) {
            return;
        }

        try {
            $user->setApiToken($newHashedPassword);
            $this->userRepository->saveUser($user);
        } catch (\Exception $ex) {
            // happens during login: if it fails, ignore it!
        }
    }
}
