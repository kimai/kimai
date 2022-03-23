<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;

abstract class BaseFormTypeQuery
{
    /**
     * @var array
     */
    private $activities = [];
    /**
     * @var array
     */
    private $projects = [];
    /**
     * @var array
     */
    private $customers = [];
    /**
     * @var User
     */
    private $user;
    /**
     * @var array<Team>
     */
    private $teams = [];

    /**
     * @param Activity|int $activity
     * @return self
     */
    public function addActivity($activity): self
    {
        if (null !== $activity) {
            $this->activities[] = $activity;
        }

        return $this;
    }

    /**
     * @param Activity[]|int[] $activities
     * @return self
     */
    public function setActivities(array $activities): self
    {
        $this->activities = $activities;

        return $this;
    }

    public function getActivities(): array
    {
        return $this->activities;
    }

    public function hasActivities(): bool
    {
        return !empty($this->activities);
    }

    /**
     * @param Project|int $project
     * @return self
     */
    public function addProject($project): self
    {
        if (null !== $project) {
            $this->projects[] = $project;
        }

        return $this;
    }

    /**
     * @param Project[]|int[] $projects
     * @return self
     */
    public function setProjects(array $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    /**
     * @return array
     */
    public function getProjects(): array
    {
        return $this->projects;
    }

    public function hasProjects(): bool
    {
        return !empty($this->projects);
    }

    /**
     * @param Customer[]|int[] $customers
     * @return self
     */
    public function setCustomers(array $customers): self
    {
        $this->customers = $customers;

        return $this;
    }

    /**
     * @param Customer|int $customer
     * @return self
     */
    public function addCustomer($customer): self
    {
        $this->customers[] = $customer;

        return $this;
    }

    public function getCustomers(): array
    {
        return $this->customers;
    }

    public function hasCustomers(): bool
    {
        return !empty($this->customers);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function addTeam(Team $team): self
    {
        $this->teams[$team->getId()] = $team;

        return $this;
    }

    /**
     * @param Team[] $teams
     * @return self
     */
    public function setTeams(array $teams): self
    {
        $this->teams = $teams;

        return $this;
    }

    /**
     * @return Team[]
     */
    public function getTeams(): array
    {
        return array_values($this->teams);
    }
}
