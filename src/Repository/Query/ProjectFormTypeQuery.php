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
     * @param Project|int|null $project
     * @param Customer|int|null $customer
     */
    public function __construct($project = null, $customer = null)
    {
        $this->project = $project;
        $this->customer = $customer;
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
