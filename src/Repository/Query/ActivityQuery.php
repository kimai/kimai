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
     * @var Project|int
     */
    protected $project;
    /**
     * @var bool
     */
    protected $orderGlobalsFirst = false;
    /**
     * @var bool
     */
    protected $globalsOnly = false;

    /**
     * @return bool
     */
    public function isOrderGlobalsFirst(): bool
    {
        return $this->orderGlobalsFirst;
    }

    /**
     * @param bool $orderGlobalsFirst
     * @return ActivityQuery
     */
    public function setOrderGlobalsFirst(bool $orderGlobalsFirst)
    {
        $this->orderGlobalsFirst = $orderGlobalsFirst;

        return $this;
    }

    /**
     * @return bool
     */
    public function isGlobalsOnly(): bool
    {
        return $this->globalsOnly;
    }

    /**
     * @param bool $globalsOnly
     * @return ActivityQuery
     */
    public function setGlobalsOnly(bool $globalsOnly)
    {
        $this->globalsOnly = $globalsOnly;

        return $this;
    }

    /**
     * @return Project|int
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project|int $project
     * @return $this
     */
    public function setProject($project = null)
    {
        $this->project = $project;

        return $this;
    }
}
