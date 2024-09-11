<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_activities_meta')]
#[ORM\UniqueConstraint(columns: ['activity_id', 'name'])]
#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
class ActivityMeta implements MetaTableTypeInterface
{
    use MetaTableTypeTrait;

    #[ORM\ManyToOne(targetEntity: Activity::class, inversedBy: 'meta')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Activity $activity = null;

    public function setEntity(EntityWithMetaFields $entity): MetaTableTypeInterface
    {
        if (!($entity instanceof Activity)) {
            throw new \InvalidArgumentException(
                \sprintf('Expected instanceof Activity, received "%s"', \get_class($entity))
            );
        }
        $this->activity = $entity;

        return $this;
    }

    /**
     * @return Activity|null
     */
    public function getEntity(): ?EntityWithMetaFields
    {
        return $this->activity;
    }
}
