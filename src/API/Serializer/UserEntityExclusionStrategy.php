<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Serializer;

use App\Entity\User;
use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Removes the detail fields (serializer group "User_Entity") from serialized users,
 * unless the user is one of the explicitly allowed users and not embedded in another user.
 *
 * Detail fields like preferences (e.g. hourly rate and work contract) are filtered
 * for the current viewer via the PrepareUserEvent. That only happens for the
 * user(s) the API was asked for, but not for users embedded in them (e.g. the supervisor).
 * Embedded users are therefore reduced to their basic fields.
 */
#[Exclude]
final class UserEntityExclusionStrategy implements ExclusionStrategyInterface
{
    private const ENTITY_GROUP = 'User_Entity';

    /**
     * @var \SplObjectStorage<User, null>
     */
    private \SplObjectStorage $allowed;

    /**
     * @param iterable<User> $allowed users which were prepared for the current viewer
     */
    public function __construct(iterable $allowed)
    {
        $this->allowed = new \SplObjectStorage();
        foreach ($allowed as $user) {
            $this->allowed->offsetSet($user);
        }
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
    {
        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        if ($property->class !== User::class || !$this->isEntityOnlyProperty($property)) {
            return false;
        }

        $user = $context instanceof SerializationContext ? $context->getObject() : null;
        if (!$user instanceof User) {
            // there is no user to check: never expose the detail fields
            return true;
        }

        return !$this->allowed->offsetExists($user) || $this->isEmbedded($context);
    }

    /**
     * Whether the visited user is part of another user, e.g. as supervisor.
     */
    private function isEmbedded(SerializationContext $context): bool
    {
        $users = 0;
        foreach ($context->getVisitingStack() as $object) {
            if ($object instanceof User && ++$users > 1) {
                return true;
            }
        }

        return false;
    }

    private function isEntityOnlyProperty(PropertyMetadata $property): bool
    {
        if (!\is_array($property->groups)) {
            return false;
        }

        return \in_array(self::ENTITY_GROUP, $property->groups, true) && !\in_array('Default', $property->groups, true);
    }
}
