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
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @deprecated since 2.54 - see https://www.kimai.org/en/blog/2026/removing-api-passwords
 */
class ApiUserRepository implements UserLoaderInterface, PasswordUpgraderInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        try {
            return $this->userRepository->loadUserByIdentifier($identifier);
        } catch (UserNotFoundException $ex) {
            return null;
        }
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
