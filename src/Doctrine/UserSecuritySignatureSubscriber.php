<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Resets the security signature of a user, if security relevant fields were changed.
 *
 * Does intentionally NOT implement DataSubscriberInterface, it must never be deactivated.
 */
#[AsDoctrineListener(event: Events::onFlush)]
final class UserSecuritySignatureSubscriber implements EventSubscriber
{
    private const FIELDS = [
        'email',
        'password',
        'totpEnabled',
        'enabled',
    ];

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
        $meta = $em->getClassMetadata(User::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!($entity instanceof User)) {
                continue;
            }

            $changes = array_intersect(self::FIELDS, array_keys($uow->getEntityChangeSet($entity)));
            if (\count($changes) === 0) {
                continue;
            }

            $entity->resetSecuritySignature();
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        }
    }
}
