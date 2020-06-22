<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use App\Entity\Timesheet;
use App\Timesheet\CalculatorInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * A listener to make sure all Timesheet entries will have a proper duration.
 */
class TimesheetSubscriber implements EventSubscriber
{
    /**
     * @var CalculatorInterface[]
     */
    protected $calculator;

    /**
     * @param CalculatorInterface[] $calculators
     */
    public function __construct(iterable $calculators)
    {
        $this->calculator = $calculators;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata(Timesheet::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!($entity instanceof Timesheet)) {
                continue;
            }

            $this->calculateFields($entity);
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        }

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!($entity instanceof Timesheet)) {
                continue;
            }

            $this->calculateFields($entity);
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        }
    }

    /**
     * @param Timesheet $entity
     */
    protected function calculateFields(Timesheet $entity)
    {
        foreach ($this->calculator as $calculator) {
            $calculator->calculate($entity);
        }
    }
}
