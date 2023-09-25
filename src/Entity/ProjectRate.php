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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_projects_rates')]
#[ORM\UniqueConstraint(columns: ['user_id', 'project_id'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ProjectRateRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity(['user', 'project'], ignoreNull: false)]
#[Serializer\ExclusionPolicy('all')]
class ProjectRate implements RateInterface
{
    use Rate;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Project $project = null;

    public function setProject(?Project $project): ProjectRate
    {
        $this->project = $project;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function getScore(): int
    {
        return 3;
    }
}
