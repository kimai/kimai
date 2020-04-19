<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="kimai2_timesheet_meta",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"timesheet_id", "name"})
 *      }
 * )
 */
class TimesheetMeta implements MetaTableTypeInterface
{
    use MetaTableTypeTrait;

    /**
     * @var Timesheet
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Timesheet", inversedBy="meta")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $timesheet;

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

    public function getEntity(): ?EntityWithMetaFields
    {
        return $this->timesheet;
    }
}
