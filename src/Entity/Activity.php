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
 * @ORM\Table(name="kimai2_activities",
 *     indexes={
 *          @ORM\Index(columns={"visible","project_id"}),
 *          @ORM\Index(columns={"visible","project_id","name"}),
 *          @ORM\Index(columns={"visible","name"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 *
 * columns={"visible","name"}               => IDX_8811FE1C7AB0E8595E237E06         => activity administration without filter
 * columns={"visible","project_id"}         => IDX_8811FE1C7AB0E859166D1F9C         => activity administration with customer or project filter
 * columns={"visible","project_id","name"}  => IDX_8811FE1C7AB0E859166D1F9C5E237E06 => activity drop-down for global activities in toolbar or globalsOnly filter in activity administration
 */
class Activity implements EntityWithMetaFields
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $project;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=150, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=150, allowEmptyString=false)
     */
    private $name;
    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;
    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $visible = true;

    // keep the trait include exactly here, for placing the column at the correct position
    use ColorTrait;
    use BudgetTrait;

    /**
     * @var ActivityMeta[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ActivityMeta", mappedBy="activity", cascade={"persist"})
     */
    private $meta;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isGlobal(): bool
    {
        return $this->project === null;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @deprecated since 1.4
     */
    public function getVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @internal only here for symfony forms
     * @return Collection|MetaTableTypeInterface[]
     */
    public function getMetaFields(): Collection
    {
        return $this->meta;
    }

    /**
     * @return MetaTableTypeInterface[]
     */
    public function getVisibleMetaFields(): array
    {
        $all = [];
        foreach ($this->meta as $meta) {
            if ($meta->isVisible()) {
                $all[] = $meta;
            }
        }

        return $all;
    }

    public function getMetaField(string $name): ?MetaTableTypeInterface
    {
        foreach ($this->meta as $field) {
            if (strtolower($field->getName()) === strtolower($name)) {
                return $field;
            }
        }

        return null;
    }

    public function setMetaField(MetaTableTypeInterface $meta): EntityWithMetaFields
    {
        if (null === ($current = $this->getMetaField($meta->getName()))) {
            $meta->setEntity($this);
            $this->meta->add($meta);

            return $this;
        }

        $current->merge($meta);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->meta = new ArrayCollection();
        }
    }
}
