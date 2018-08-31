<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Activity;

/**
 * Custom form field type to select an activity which are grouped by their Projects, preceeded by their customer names.
 */
class ActivityGroupedWithCustomerNameType extends ActivityType
{
    /**
     * @param Activity $activity
     * @param $key
     * @param $index
     * @return string
     */
    public function groupBy(Activity $activity, $key, $index)
    {
        if (null === $activity->getProject()) {
            return null;
        }

        return $activity->getProject()->getCustomer()->getName();
    }

    /**
     * @param Activity $activity
     * @return string
     */
    public function choiceLabel(Activity $activity)
    {
        if (null === $activity->getProject()) {
            return $activity->getName();
        }

        return $activity->getProject()->getName() . ': ' . $activity->getName();
    }
}
