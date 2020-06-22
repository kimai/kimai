<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @App\Validator\Constraints\TimesheetMultiUser
 */
final class MultiUserTimesheet extends Timesheet
{
    /**
     * @var Collection<User>
     */
    private $users;
    /**
     * @var Collection<Team>
     */
    private $teams;

    public function __construct()
    {
        parent::__construct();
        $this->users = new ArrayCollection();
        $this->teams = new ArrayCollection();
    }

    /**
     * @return Collection<User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user)
    {
        $this->users->add($user);

        return $this;
    }

    public function removeUser(User $user)
    {
        if ($this->users->contains($user)) {
            $this->users->remove($user);
        }

        return $this;
    }

    /**
     * @return Collection<Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team)
    {
        $this->teams->add($team);

        return $this;
    }

    public function removeTeam(Team $team)
    {
        if ($this->teams->contains($team)) {
            $this->teams->remove($team);
        }

        return $this;
    }
}
