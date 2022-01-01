<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @deprecated will be removed with 2.0
 */
final class CurrentUser
{
    /**
     * @var TokenStorageInterface
     */
    private $storage;
    /**
     * @var User|null
     */
    private $user;

    public function __construct(TokenStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function getUser(): ?User
    {
        if (null !== $this->user) {
            return $this->user;
        }

        if (null === $this->storage->getToken()) {
            return null;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        if (!($user instanceof User)) {
            return null;
        }

        @trigger_error('CurrentUser is deprecated and will be removed with 2.0, use DI or at worst Symfony\Component\Security\Core\Security instead', E_USER_DEPRECATED);

        $this->user = $user;

        return $this->user;
    }
}
