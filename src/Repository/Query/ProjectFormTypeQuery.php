<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;

final class ProjectFormTypeQuery
{
    /**
     * @var Customer|int|null
     */
    private $customer;
    /**
     * @var Project|int|null
     */
    private $project;
    /**
     * @var Project|null
     */
    private $projectToIgnore;
    /**
     * @var User
     */
    private $user;
    /**
     * @var array<Team>
     */
    private $teams = [];

    /**
     * @param Project|int|null $project
     * @param Customer|int|null $customer
     */
    public function __construct($project = null, $customer = null)
    {
        $this->project = $project;
        $this->customer = $customer;
    }

    public function addTeam(Team $team): ProjectFormTypeQuery
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

    public function setUser(User $user): ProjectFormTypeQuery
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Customer|int|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer|int|null $customer
     * @return $this
     */
    public function setCustomer($customer): ProjectFormTypeQuery
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Project|int|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project|int|null $project
     * @return ProjectFormTypeQuery
     */
    public function setProject($project): ProjectFormTypeQuery
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Project|null
     */
    public function getProjectToIgnore(): ?Project
    {
        return $this->projectToIgnore;
    }

    public function setProjectToIgnore(Project $projectToIgnore): ProjectFormTypeQuery
    {
        $this->projectToIgnore = $projectToIgnore;

        return $this;
    }
}
