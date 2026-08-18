<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Serializer;

use App\Entity\Timesheet;
use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Removes the monetary fields from serialized timesheets, unless the current
 * user is granted the "view_rate" permission for that record: see GHSA-fq95-vwvx-w88f.
 *
 * The decision is made per record, so a collection can contain records of
 * multiple users, each one serialized according to the callers permission.
 */
final class RateExclusionStrategy implements ExclusionStrategyInterface
{
    private const RATE_FIELDS = ['rate', 'internalRate', 'fixedRate', 'hourlyRate'];

    /**
     * @var \SplObjectStorage<Timesheet, bool>
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
        if ($property->class !== Timesheet::class || !\in_array($property->name, self::RATE_FIELDS, true)) {
            return false;
        }

        $timesheet = $context instanceof SerializationContext ? $context->getObject() : null;
        if (!$timesheet instanceof Timesheet) {
            // there is no record to check the permission on: never expose rates
            return true;
        }

        if (!$this->decisions->offsetExists($timesheet)) {
            $this->decisions[$timesheet] = !$this->security->isGranted('view_rate', $timesheet);
        }

        return $this->decisions[$timesheet];
    }
}
