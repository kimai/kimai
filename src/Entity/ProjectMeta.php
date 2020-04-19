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
 * @ORM\Table(name="kimai2_projects_meta",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"project_id", "name"})
 *      }
 * )
 */
class ProjectMeta implements MetaTableTypeInterface
{
    use MetaTableTypeTrait;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="meta")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $project;

    public function setEntity(EntityWithMetaFields $entity): MetaTableTypeInterface
    {
        if (!($entity instanceof Project)) {
            throw new \InvalidArgumentException(
                sprintf('Expected instanceof Project, received "%s"', \get_class($entity))
            );
        }
        $this->project = $entity;

        return $this;
    }

    public function getEntity(): ?EntityWithMetaFields
    {
        return $this->project;
    }
}
