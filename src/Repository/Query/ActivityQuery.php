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
    /**
     * @var Project|int|null
     */
    private $project;
    /**
     * @var bool
     */
    private $globalsOnly = false;

    public function __construct()
    {
        parent::__construct();
        $this->setOrderBy('name');
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
     * @return ActivityQuery
     */
    public function setGlobalsOnly($globalsOnly): ActivityQuery
    {
        $this->globalsOnly = (bool) $globalsOnly;

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
     * @return ActivityQuery
     */
    public function setProject($project = null): ActivityQuery
    {
        $this->project = $project;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirty(): bool
    {
        if (parent::isDirty()) {
            return true;
        }

        if ($this->project !== null) {
            return true;
        }

        if ($this->globalsOnly !== false) {
            return true;
        }

        return false;
    }
}
