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

final class ProjectFormTypeQuery extends BaseFormTypeQuery
{
    /**
     * @var \DateTime|null
     */
    private $projectStart;
    /**
     * @var \DateTime|null
     */
    private $projectEnd;
    /**
     * @var Project|null
     */
    private $projectToIgnore;
    private $ignoreDate = false;
    private $withCustomer = false;

    /**
     * @param Project|int|null|array<int>|array<Project> $project
     * @param Customer|int|null|array<int>|array<Customer> $customer
     */
    public function __construct($project = null, $customer = null)
    {
        if (null !== $project) {
            if (!\is_array($project)) {
                $project = [$project];
            }
            $this->setProjects($project);
        }

        if (null !== $customer) {
            if (!\is_array($customer)) {
                $customer = [$customer];
            }
            $this->setCustomers($customer);
        }

        $this->projectStart = $this->projectEnd = new \DateTime();
    }

    /**
     * Whether customers should be joined
     *
     * @return bool
     */
    public function withCustomer(): bool
    {
        return $this->withCustomer;
    }

    /**
     * Directly join the customer
     *
     * @param bool $withCustomer
     */
    public function setWithCustomer(bool $withCustomer): void
    {
        $this->withCustomer = $withCustomer;
    }

    /**
     * @return Project|null
     */
    public function getProjectToIgnore(): ?Project
    {
        return $this->projectToIgnore;
    }

    public function setProjectToIgnore(Project $projectToIgnore): void
    {
        $this->projectToIgnore = $projectToIgnore;
    }

    public function isIgnoreDate(): bool
    {
        return $this->ignoreDate;
    }

    public function setIgnoreDate(bool $ignoreDate): void
    {
        $this->ignoreDate = $ignoreDate;
    }

    public function getProjectStart(): ?\DateTime
    {
        return $this->projectStart;
    }

    public function setProjectStart(?\DateTime $projectStart): void
    {
        $this->projectStart = $projectStart;
    }

    public function getProjectEnd(): ?\DateTime
    {
        return $this->projectEnd;
    }

    public function setProjectEnd(?\DateTime $projectEnd): void
    {
        $this->projectEnd = $projectEnd;
    }
}
