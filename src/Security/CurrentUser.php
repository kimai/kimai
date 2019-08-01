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

class CurrentUser
{
    /**
     * @var TokenStorageInterface
     */
    protected $storage;
    /**
     * @var UserRepository
     */
    protected $repository;

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

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        if (!($user instanceof User)) {
            return null;
        }

        return $this->repository->getUserById($user->getId());
    }
}
