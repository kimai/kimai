<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

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

        /** @var array<int, User> $users */
        $users = [];

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                $changes = array_intersect(self::FIELDS, array_keys($uow->getEntityChangeSet($entity)));
                if (\count($changes) > 0) {
                    $users[spl_object_id($entity)] = $entity;
                }
            } elseif ($entity instanceof UserPreference) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (\array_key_exists('value', $changeSet) && $this->isPasswordReset($entity, $changeSet['value'][1]) && !$this->isPasswordResetValue($changeSet['value'][0])) {
                    $this->addUser($users, $entity->getUser(), $uow);
                }
            }
        }

        // the preference is created when an administrator requires a password reset for the first time
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof UserPreference && $this->isPasswordReset($entity, $entity->getValue())) {
                $this->addUser($users, $entity->getUser(), $uow);
            }
        }

        foreach ($users as $user) {
            $user->resetSecuritySignature();
            $uow->recomputeSingleEntityChangeSet($meta, $user);
        }
    }

    private function isPasswordReset(UserPreference $preference, mixed $value): bool
    {
        return $preference->getName() === User::PREFERENCE_PASSWORD_RESET && $this->isPasswordResetValue($value);
    }

    private function isPasswordResetValue(mixed $value): bool
    {
        return \is_scalar($value) && (string) $value === '1';
    }

    /**
     * @param array<int, User> $users
     */
    private function addUser(array &$users, ?User $user, UnitOfWork $uow): void
    {
        // new users do not have any sessions or remember-me cookies, which could be invalidated
        if ($user === null || $uow->isScheduledForInsert($user)) {
            return;
        }

        $users[spl_object_id($user)] = $user;
    }
}
