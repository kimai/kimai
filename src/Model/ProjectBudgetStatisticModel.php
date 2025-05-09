<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Project;

/**
 * Object used to unify the access to budget data in charts.
 *
 * @method Project getEntity()
 */
class ProjectBudgetStatisticModel extends BudgetStatisticModel
{
    public function __construct(Project $project)
    {
        parent::__construct($project);
    }

    public function getProject(): Project
    {
        return $this->getEntity();
    }
}
