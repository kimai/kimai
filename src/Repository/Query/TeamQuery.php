<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\User;

class TeamQuery extends BaseQuery
{
    public const TEAM_ORDER_ALLOWED = ['id', 'name', 'teamlead'];

    /**
     * @var User[]
     */
    private $users = [];

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'name',
        ]);
    }

    public function hasUsers(): bool
    {
        return !empty($this->users);
    }

    public function addUser(User $user): self
    {
        $this->users[$user->getId()] = $user;

        return $this;
    }

    public function removeUser(User $user): self
    {
        if (isset($this->users[$user->getId()])) {
            unset($this->users[$user->getId()]);
        }

        return $this;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return array_values($this->users);
    }
}
