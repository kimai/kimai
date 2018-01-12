<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Entity\Timesheet;

/**
 * A listener to make sure all Timesheet entries will have a proper duration.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetListener implements EventSubscriber
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
                $entity->setDuration($entity->getEnd()->getTimestamp() - $entity->getBegin()->getTimestamp());
            }

            // TODO calculate hourly rate
        }
    }
}
