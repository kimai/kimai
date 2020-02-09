<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_projects_rates",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"user_id", "project_id"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRateRepository")
 * @UniqueEntity({"user", "project"}, ignoreNull=false)
 */
class ProjectRate implements RateInterface
{
    use Rate;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull
     */
    private $project;

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
