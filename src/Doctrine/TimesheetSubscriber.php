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
    private $calculator;
    /**
     * @var CalculatorInterface[]
     */
    private $sorted;

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

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata(Timesheet::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!($entity instanceof Timesheet)) {
                continue;
            }

            $this->calculateFields($entity, $uow->getEntityChangeSet($entity));
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

    protected function calculateFields(Timesheet $entity, array $changes = []): void
    {
        if ($this->sorted === null) {
            $this->sorted = [];

            foreach ($this->calculator as $calculator) {
                $i = 0;
                $prio = 1000;
                if (method_exists($calculator, 'getPriority')) {
                    $prio = $calculator->getPriority();
                }

                do {
                    $key = $prio + $i++;
                } while (\array_key_exists($key, $this->sorted));

                $this->sorted[$key] = $calculator;
            }

            ksort($this->sorted);
        }

        foreach ($this->sorted as $calculator) {
            /* @phpstan-ignore-next-line */
            $calculator->calculate($entity, $changes);
        }
    }
}
