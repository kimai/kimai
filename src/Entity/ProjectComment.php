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
 * @ORM\Table(name="kimai2_projects_comments",
 *      indexes={
 *          @ORM\Index(columns={"project_id"})
 *      }
 * )
 */
class ProjectComment
{
    use CommentTableTypeTrait;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $project;

    public function setProject(Project $project): ProjectComment
    {
        $this->project = $project;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }
}
