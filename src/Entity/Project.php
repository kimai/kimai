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
 * @ORM\Table(name="kimai2_projects")
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 */
class Project implements EntityWithMetaFields
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
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="projects")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $customer;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotNull()
     * @Assert\Length(min=2, max=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="order_number", type="text", length=20, nullable=true)
     * @Assert\Length(max=20)
     */
    private $orderNumber;

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
     * @var Activity[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Activity", mappedBy="project")
     */
    private $activities;

    // keep the trait include exactly here, for placing the column at the correct position
    use RatesTrait;
    use ColorTrait;
    use BudgetTrait;

    /**
     * @var Timesheet[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Timesheet", mappedBy="project")
     */
    private $timesheets;

    /**
     * @var ProjectMeta[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ProjectMeta", mappedBy="project", cascade={"persist"})
     */
    private $meta;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->timesheets = new ArrayCollection();
        $this->meta = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): Project
    {
        $this->customer = $customer;

        return $this;
    }

    public function setName(string $name): Project
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $comment
     * @return Project
     */
    public function setComment($comment): Project
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setVisible(bool $visible): Project
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return bool
     */
    public function getVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @return Collection<Timesheet>
     */
    public function getTimesheets(): Collection
    {
        return $this->timesheets;
    }

    /**
     * @return Collection<Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    /**
     * @return string|null
     */
    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    /**
     * @param string $orderNumber
     * @return Project
     */
    public function setOrderNumber($orderNumber): Project
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * @internal only here for symfony forms
     * @return Collection|MetaTableTypeInterface[]
     */
    public function getMetaFields(): Collection
    {
        return $this->meta;
    }

    public function getMetaField(string $name): ?MetaTableTypeInterface
    {
        foreach ($this->meta as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }

    public function setMetaField(MetaTableTypeInterface $meta): EntityWithMetaFields
    {
        $meta->setEntity($this);
        $this->meta->add($meta);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
