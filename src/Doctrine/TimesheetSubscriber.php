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

    public function getSubscribedEvents(): array
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

            // TODO make this behavior configurable with SystemConfiguration
            $changes = $uow->getEntityChangeSet($entity);

            // check if the rate was changed manually
            $changedRate = false;
            foreach (['hourlyRate', 'fixedRate', 'internalRate', 'rate'] as $field) {
                if (\array_key_exists($field, $changes)) {
                    $changedRate = true;
                    break;
                }
            }

            // if no manual rate changed was applied:
            // check if a field changed, that is relevant for the rate calculation: if one was changed =>
            // reset all rates, because most users do not even see their rates and would not be able
            // to fix or empty the rate, even if they knew that the changed project has another base rate
            if (!$changedRate) {
                foreach (['project', 'activity', 'user'] as $field) {
                    if (\array_key_exists($field, $changes)) {
                        // TODO this is a problem for everyone using manual rates instead of calculated ones
                        $entity->resetRates();
                        break;
                    }
                }
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
