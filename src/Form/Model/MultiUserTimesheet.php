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
     * @var ArrayCollection<int, User>
     */
    private ArrayCollection $users;
    /**
     * @var ArrayCollection<int, Team>
     */
    private ArrayCollection $teams;

    public function __construct()
    {
        parent::__construct();
        $this->users = new ArrayCollection();
        $this->teams = new ArrayCollection();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): void
    {
        if ($user->getId() === null) {
            throw new \InvalidArgumentException('Cannot use unpersisted $user');
        }
        $this->users->set($user->getId(), $user);
    }

    public function removeUser(User $user): void
    {
        if ($user->getId() === null) {
            throw new \InvalidArgumentException('Cannot use unpersisted $user');
        }
        $this->users->remove($user->getId());
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): void
    {
        if ($team->getId() === null) {
            throw new \InvalidArgumentException('Cannot use unpersisted $team');
        }
        $this->teams->set($team->getId(), $team);
    }

    public function removeTeam(Team $team): void
    {
        if ($team->getId() === null) {
            throw new \InvalidArgumentException('Cannot use unpersisted $team');
        }
        $this->teams->remove($team->getId());
    }
}
