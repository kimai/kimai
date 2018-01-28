<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use App\Entity\UserPreference;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Entity\Timesheet;

/**
 * A listener to make sure all Timesheet entries will have a proper duration.
 */
class TimesheetSubscriber implements EventSubscriber
{

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'preUpdate',
        );
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->calculateFields($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->calculateFields($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    protected function calculateFields(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof Timesheet) {
            if ($entity->getEnd() !== null) {
                $duration = $entity->getEnd()->getTimestamp() - $entity->getBegin()->getTimestamp();
                $entity->setDuration($duration);

                // TODO allow to set hourly rate on activity, project and customer and prefer these

                $rate = $this->calculateRate($entity);
                $entity->setRate($rate);
            }
        }
    }

    /**
     * @param Timesheet $entity
     * @return float
     */
    protected function calculateRate(Timesheet $entity)
    {
        $hourlyRate = (float) $entity->getUser()->getPreferenceValue(UserPreference::HOURLY_RATE, 0);
        return (float) $hourlyRate * ($entity->getDuration() / 3600);
    }
}
