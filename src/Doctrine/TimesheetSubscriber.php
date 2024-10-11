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
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * A listener to make sure all Timesheet entries will be calculated properly.
 *
 * E.g. updates timesheet records and applies configured rate & rounding rules.
 */
#[AsDoctrineListener(event: Events::onFlush, priority: 50)]
final class TimesheetSubscriber implements EventSubscriber, DataSubscriberInterface
{
    /**
     * @var CalculatorInterface[]
     */
    private ?array $sorted = null;

    /**
     * @param CalculatorInterface[] $calculators
     */
    public function __construct(
        #[TaggedIterator(CalculatorInterface::class)]
        private readonly iterable $calculators
    )
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
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

    private function calculateFields(Timesheet $entity, array $changes = []): void
    {
        if ($this->sorted === null) {
            $this->sorted = [];

            foreach ($this->calculators as $calculator) {
                $i = 0;
                $prio = $calculator->getPriority();

                do {
                    $key = $prio + $i++;
                } while (\array_key_exists($key, $this->sorted));

                $this->sorted[$key] = $calculator;
            }

            ksort($this->sorted);
        }

        foreach ($this->sorted as $calculator) {
            $calculator->calculate($entity, $changes);
        }
    }
}
