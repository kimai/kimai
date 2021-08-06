<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Activity;

/**
 * Object used to unify the access to budget data in charts.
 *
 * @internal do not use in plugins, no BC promise given!
 * @method Activity getEntity()
 */
class ActivityBudgetStatisticModel extends BudgetStatisticModel
{
    public function __construct(Activity $activity)
    {
        parent::__construct($activity);
    }

    public function getActivity(): Activity
    {
        return $this->getEntity();
    }
}
