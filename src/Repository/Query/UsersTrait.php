<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\User;

trait UsersTrait
{
    /**
     * @var array<User>
     */
    protected array $users = [];

    public function addUser(User $user): void
    {
        $this->users[$user->getId()] = $user;
    }

    public function removeUser(User $user): void
    {
        if (isset($this->users[$user->getId()])) {
            unset($this->users[$user->getId()]);
        }
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return array_values($this->users);
    }

    /**
     * Check if there is one or more users in the query
     */
    public function hasUsers(): bool
    {
        return \count($this->users) > 0;
    }
}
