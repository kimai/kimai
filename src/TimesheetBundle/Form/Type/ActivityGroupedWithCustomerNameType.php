<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form\Type;

use TimesheetBundle\Entity\Activity;

/**
 * Custom form field type to select an activity which are grouped by their Projects, preceeded by their customer names.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
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
        return $activity->getProject()->getCustomer()->getName();
    }

    /**
     * @param Activity $activity
     * @return string
     */
    public function choiceLabel(Activity $activity)
    {
        return $activity->getProject()->getName() . ': ' . $activity->getName();
    }
}
