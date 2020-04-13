<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_tags",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"name"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\TagRepository")
 * @UniqueEntity("name")
 */
class Tag
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=100, allowEmptyString=false)
     * @Assert\Regex(pattern="/,/",match=false,message="Tag name cannot contain comma")
     */
    private $name;

    use ColorTrait;

    /**
     * @var Timesheet[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Timesheet", mappedBy="tags", fetch="EXTRA_LAZY")
     */
    protected $timesheets;

    public function __construct()
    {
        $this->timesheets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $tagName): Tag
    {
        $this->name = $tagName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addTimesheet(Timesheet $timesheet)
    {
        if ($this->timesheets->contains($timesheet)) {
            return;
        }

        $this->timesheets->add($timesheet);
        $timesheet->addTag($this);
    }

    public function removeTimesheet(Timesheet $timesheet)
    {
        if (!$this->timesheets->contains($timesheet)) {
            return;
        }

        $this->timesheets->removeElement($timesheet);
        $timesheet->removeTag($this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
