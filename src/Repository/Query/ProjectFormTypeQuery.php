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
     * @var Project|null
     */
    private $projectToIgnore;
    /**
     * @var bool
     */
    private $ignoreDate = false;

    /**
     * @param Project|int|null $project
     * @param Customer|int|null $customer
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

    public function isIgnoreDate(): bool
    {
        return $this->ignoreDate;
    }

    public function setIgnoreDate(bool $ignoreDate): ProjectFormTypeQuery
    {
        $this->ignoreDate = $ignoreDate;

        return $this;
    }
}
