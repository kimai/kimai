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

#[ORM\Table(name: 'kimai2_timesheet_meta')]
#[ORM\UniqueConstraint(columns: ['timesheet_id', 'name'])]
#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
class TimesheetMeta implements MetaTableTypeInterface
{
    use MetaTableTypeTrait;

    #[ORM\ManyToOne(targetEntity: Timesheet::class, inversedBy: 'meta')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Timesheet $timesheet = null;

    public function setEntity(EntityWithMetaFields $entity): MetaTableTypeInterface
    {
        if (!($entity instanceof Timesheet)) {
            throw new \InvalidArgumentException(
                sprintf('Expected instanceof Timesheet, received "%s"', \get_class($entity))
            );
        }
        $this->timesheet = $entity;

        return $this;
    }

    /**
     * @return Timesheet|null
     */
    public function getEntity(): ?EntityWithMetaFields
    {
        return $this->timesheet;
    }
}
