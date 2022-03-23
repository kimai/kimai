<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Project;

/**
 * Can be used for advanced queries with the: ActivityRepository
 */
class ActivityQuery extends ProjectQuery
{
    public const ACTIVITY_ORDER_ALLOWED = ['id', 'name', 'comment', 'customer', 'project', 'budget', 'timeBudget', 'visible'];

    /**
     * @var array<Project|int>
     */
    private $projects = [];
    private bool $globalsOnly = false;
    private bool $excludeGlobals = false;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults([
            'orderBy' => 'name',
            'projects' => [],
            'globalsOnly' => false,
            'excludeGlobals' => false,
        ]);
    }

    /**
     * @return bool
     */
    public function isGlobalsOnly(): bool
    {
        return (bool) $this->globalsOnly;
    }

    /**
     * @param bool $globalsOnly
     * @return self
     */
    public function setGlobalsOnly(bool $globalsOnly): self
    {
        $this->globalsOnly = $globalsOnly;

        return $this;
    }

    public function isExcludeGlobals(): bool
    {
        return $this->excludeGlobals;
    }

    public function setExcludeGlobals(bool $excludeGlobals): self
    {
        $this->excludeGlobals = $excludeGlobals;

        return $this;
    }

    /**
     * @param Project|int $project
     * @return self
     */
    public function addProject(Project|int $project): self
    {
        $this->projects[] = $project;

        return $this;
    }

    public function setProjects(array $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    public function getProjects(): array
    {
        return $this->projects;
    }

    public function hasProjects(): bool
    {
        return !empty($this->projects);
    }
}
