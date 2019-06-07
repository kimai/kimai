<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_activities")
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 */
class Activity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="activities")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", length=65535, nullable=true)
     */
    private $comment;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $visible = true;

    /**
     * @var Timesheet[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Timesheet", mappedBy="activity")
     */
    private $timesheets;

    // keep the trait include exactly here, for placing the column at the correct position
    use RatesTrait;
    use ColorTrait;

    public function __construct()
    {
        $this->timesheets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<Timesheet>
     */
    public function getTimesheets(): Collection
    {
        return $this->timesheets;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): Activity
    {
        $this->project = $project;

        return $this;
    }

    public function setName(string $name): Activity
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setComment(?string $comment): Activity
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setVisible(bool $visible): Activity
    {
        $this->visible = $visible;

        return $this;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
