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
use App\Validator\Constraints as Constraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[Constraints\TimesheetMultiUser]
final class MultiUserTimesheet extends Timesheet
{
    /**
     * @var Collection<User>
     */
    private Collection $users;
    /**
     * @var Collection<Team>
     */
    private Collection $teams;

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

    public function addUser(User $user): void
    {
        $this->users->add($user);
    }

    public function removeUser(User $user): void
    {
        if ($this->users->contains($user)) {
            $this->users->remove($user);
        }
    }

    /**
     * @return Collection<Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): void
    {
        $this->teams->add($team);
    }

    public function removeTeam(Team $team): void
    {
        if ($this->teams->contains($team)) {
            $this->teams->remove($team);
        }
    }
}
