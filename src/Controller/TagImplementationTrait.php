<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Repository\Query\TimesheetQuery;
use Doctrine\Common\Persistence\ManagerRegistry;

trait TagImplementationTrait
{

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    abstract protected function getDoctrine(): ManagerRegistry;

    /**
     * @param TimesheetQuery $query
     */
    protected function prepareTagList(TimesheetQuery $query)
    {
        if ($query->hasTags() === TRUE) {
            $query->setAffectedTimesheetIdArray(
                $this->getDoctrine()->getRepository(Timesheet::class)->findIdsByTagIds(
                    $this->getDoctrine()->getRepository(Tag::class)->findIdsByTagNameList($query->getTags())
                )
            );
        }
    }
}