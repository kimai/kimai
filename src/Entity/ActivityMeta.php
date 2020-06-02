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
 * @ORM\Table(name="kimai2_activities_meta",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"activity_id", "name"})
 *      }
 * )
 */
class ActivityMeta implements MetaTableTypeInterface
{
    use MetaTableTypeTrait;

    /**
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Activity", inversedBy="meta")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $activity;

    public function setEntity(EntityWithMetaFields $entity): MetaTableTypeInterface
    {
        if (!($entity instanceof Activity)) {
            throw new \InvalidArgumentException(
                sprintf('Expected instanceof Activity, received "%s"', \get_class($entity))
            );
        }
        $this->activity = $entity;

        return $this;
    }

    public function getEntity(): ?EntityWithMetaFields
    {
        return $this->activity;
    }
}
