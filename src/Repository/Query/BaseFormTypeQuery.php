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
     * @return Activity|int|null
     * @deprecated since 1.9 - use getActivities() instead - will be removed with 2.0
     */
    public function getActivity()
    {
        if (\count($this->activities) > 0) {
            return $this->activities[0];
        }

        return null;
    }

    /**
     * @param Activity|int|null $activity
     * @return self
     * @deprecated since 1.9 - use setActivities() or addActivity() instead - will be removed with 2.0
     */
    public function setActivity($activity): self
    {
        if (null === $activity) {
            $this->activities = [];
        } else {
            $this->activities = [$activity];
        }

        return $this;
    }

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
     * @return Project|int|null
     * @deprecated since 1.9 - use getProjects() instead - will be removed with 2.0
     */
    public function getProject()
    {
        if (\count($this->projects) > 0) {
            return $this->projects[0];
        }

        return null;
    }

    /**
     * @param Project|int|null $project
     * @return self
     * @deprecated since 1.9 - use addProject() instead - will be removed with 2.0
     */
    public function setProject($project): self
    {
        if (null === $project) {
            $this->projects = [];
        } else {
            $this->projects = [$project];
        }

        return $this;
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
     * @return Customer|int|null
     * @deprecated since 1.9 - use getCustomers() instead - will be removed with 2.0
     */
    public function getCustomer()
    {
        if (\count($this->customers) > 0) {
            return $this->customers[0];
        }

        return null;
    }

    /**
     * @param Customer|int|null $customer
     * @return self
     * @deprecated since 1.9 - use addCustomer() instead - will be removed with 2.0
     */
    public function setCustomer($customer): self
    {
        if (null === $customer) {
            $this->customers = [];
        } else {
            $this->customers = [$customer];
        }

        return $this;
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
     * @return Team[]
     */
    public function getTeams(): array
    {
        return array_values($this->teams);
    }
}
