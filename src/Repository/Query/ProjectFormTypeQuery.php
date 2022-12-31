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
    private ?\DateTime $projectStart = null;
    private ?\DateTime $projectEnd = null;
    private ?Project $projectToIgnore = null;
    private bool $ignoreDate = false;
    private bool $withCustomer = false;

    /**
     * @param Project|array<Project>|int|null $project
     * @param Customer|array<Customer>|int|null $customer
     */
    public function __construct(Project|array|int|null $project = null, Customer|array|int|null $customer = null)
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

        $this->projectStart = new \DateTime();
        $this->projectEnd = clone $this->projectStart;
    }

    /**
     * Whether customers should be joined
     */
    public function withCustomer(): bool
    {
        return $this->withCustomer;
    }

    /**
     * Directly join the customer
     */
    public function setWithCustomer(bool $withCustomer): void
    {
        $this->withCustomer = $withCustomer;
    }

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
