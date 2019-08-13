<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Team;
use App\Entity\User;

/**
 * Can be used for pre-filling form types with the: UserRepository
 */
final class UserFormTypeQuery
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var array<Team>
     */
    private $teams = [];

    public function addTeam(Team $team): UserFormTypeQuery
    {
        $this->teams[$team->getId()] = $team;

        return $this;
    }

    /**
     * @return Team[]
     */
    public function getTeams(): array
    {
        return array_values($this->teams);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): UserFormTypeQuery
    {
        $this->user = $user;

        return $this;
    }
}
