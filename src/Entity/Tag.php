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
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_tags')]
#[ORM\UniqueConstraint(columns: ['name'])]
#[ORM\Entity(repositoryClass: 'App\Repository\TagRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('name')]
#[Serializer\ExclusionPolicy('all')]
class Tag
{
    /**
     * Internal Tag ID
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?int $id = null;
    /**
     * The tag name
     */
    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100, normalizer: 'trim')]
    #[Assert\Regex(pattern: '/,/', match: false, message: 'Tag name cannot contain comma')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $name = null;

    use ColorTrait;

    /**
     * @var Collection<Timesheet>
     */
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Timesheet', mappedBy: 'tags', fetch: 'EXTRA_LAZY')]
    private Collection $timesheets;

    public function __construct()
    {
        $this->timesheets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $tagName): Tag
    {
        $this->name = $tagName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addTimesheet(Timesheet $timesheet): void
    {
        if ($this->timesheets->contains($timesheet)) {
            return;
        }

        $this->timesheets->add($timesheet);
        $timesheet->addTag($this);
    }

    public function removeTimesheet(Timesheet $timesheet): void
    {
        if (!$this->timesheets->contains($timesheet)) {
            return;
        }

        $this->timesheets->removeElement($timesheet);
        $timesheet->removeTag($this);
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
