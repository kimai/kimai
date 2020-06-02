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
    public const ACTIVITY_ORDER_ALLOWED = ['id', 'name', 'comment', 'customer', 'project'];

    /**
     * @var Project[]|int[]
     */
    private $projects = [];
    /**
     * @var bool
     */
    private $globalsOnly = false;
    /**
     * @var bool
     */
    private $excludeGlobals = false;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults([
            'orderBy' => 'name',
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
    public function setGlobalsOnly($globalsOnly): self
    {
        $this->globalsOnly = (bool) $globalsOnly;

        return $this;
    }

    public function isExcludeGlobals(): bool
    {
        return (bool) $this->excludeGlobals;
    }

    public function setExcludeGlobals(bool $excludeGlobals): self
    {
        $this->excludeGlobals = (bool) $excludeGlobals;

        return $this;
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
     * @deprecated since 1.9 - use setProjects() or addProject() instead - will be removed with 2.0
     */
    public function setProject($project = null): self
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
