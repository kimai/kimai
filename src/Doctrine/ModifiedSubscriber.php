<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use App\Doctrine\Behavior\CreatedAt;
use App\Doctrine\Behavior\ModifiedAt;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Automatically set the modifiedAt field for all ModifiedAt instances.
 */
#[AsDoctrineListener(event: Events::onFlush, priority: 60)]
final class ModifiedSubscriber implements EventSubscriber, DataSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ModifiedAt) {
                $entity->setModifiedAt($now);
            }
            if ($entity instanceof CreatedAt && $entity->getCreatedAt() === null) {
                $entity->setCreatedAt($now);
            }
        }

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ModifiedAt) {
                $entity->setModifiedAt($now);
            }
            if ($entity instanceof CreatedAt && $entity->getCreatedAt() === null) {
                $entity->setCreatedAt($now);
            }
        }
    }
}
