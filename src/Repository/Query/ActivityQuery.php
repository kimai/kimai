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
     * @var Project|int|null
     */
    private $project;
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
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project|int|null $project
     * @return self
     */
    public function setProject($project = null): self
    {
        $this->project = $project;

        return $this;
    }
}
