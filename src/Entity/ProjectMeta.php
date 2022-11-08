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

/**
 * @Serializer\ExclusionPolicy("all")
 */
#[ORM\Table(name: 'kimai2_projects_meta')]
#[ORM\UniqueConstraint(columns: ['project_id', 'name'])]
#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class ProjectMeta implements MetaTableTypeInterface
{
    use MetaTableTypeTrait;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Project', inversedBy: 'meta')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    #[Assert\NotNull]
    private ?Project $project = null;

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

    /**
     * @return Project|null
     */
    public function getEntity(): ?EntityWithMetaFields
    {
        return $this->project;
    }
}
