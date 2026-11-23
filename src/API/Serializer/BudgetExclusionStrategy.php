<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Serializer;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Removes the budget fields from serialized customers, projects and activities,
 * unless the current user is granted the "budget" (monetary) or "time"
 * permission for that record.
 *
 * The decision is made per record, so a collection can contain records of
 * multiple teams, each one serialized according to the callers permission.
 */
final class BudgetExclusionStrategy implements ExclusionStrategyInterface
{
    /**
     * Field => the permissions revealing it. "budgetType" belongs to both
     * budget flavors and is serialized if one of the permissions is granted.
     */
    private const BUDGET_FIELDS = [
        'budget' => ['budget'],
        'timeBudget' => ['time'],
        'budgetType' => ['budget', 'time'],
    ];

    private const CLASSES = [Activity::class, Customer::class, Project::class];

    /**
     * @var \SplObjectStorage<object, array<string, bool>>
     */
    private \SplObjectStorage $decisions;

    public function __construct(private readonly AuthorizationCheckerInterface $security)
    {
        $this->decisions = new \SplObjectStorage();
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
    {
        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        if (!\array_key_exists($property->name, self::BUDGET_FIELDS) || !\in_array($property->class, self::CLASSES, true)) {
            return false;
        }

        $object = $context instanceof SerializationContext ? $context->getObject() : null;
        if (!$object instanceof Activity && !$object instanceof Customer && !$object instanceof Project) {
            // there is no record to check the permission on: never expose budgets
            return true;
        }

        foreach (self::BUDGET_FIELDS[$property->name] as $permission) {
            if ($this->isGranted($permission, $object)) {
                return false;
            }
        }

        return true;
    }

    private function isGranted(string $permission, object $object): bool
    {
        $decisions = $this->decisions->offsetExists($object) ? $this->decisions[$object] : [];

        if (!\array_key_exists($permission, $decisions)) {
            $decisions[$permission] = $this->security->isGranted($permission, $object);
            $this->decisions[$object] = $decisions;
        }

        return $decisions[$permission];
    }
}
