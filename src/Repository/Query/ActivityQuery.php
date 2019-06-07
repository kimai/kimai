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

    public function setOrderGlobalsFirst(bool $orderGlobalsFirst): ActivityQuery
    {
        $this->orderGlobalsFirst = $orderGlobalsFirst;

        return $this;
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
}
