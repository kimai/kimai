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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class CurrentUser
{
    /**
     * @var TokenStorageInterface
     */
    private $storage;
    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var User|null
     */
    private $user;

    /**
     * @param TokenStorageInterface $storage
     * @param UserRepository $repository
     */
    public function __construct(TokenStorageInterface $storage, UserRepository $repository)
    {
        $this->storage = $storage;
        $this->repository = $repository;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        if (null === $this->storage->getToken()) {
            return null;
        }

        // some inline caching to prevent multiple DB lookups
        if (null !== $this->user) {
            return $this->user;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        if (!($user instanceof User)) {
            return null;
        }

        $this->user = $this->repository->getUserById($user->getId());

        return $this->user;
    }
}
