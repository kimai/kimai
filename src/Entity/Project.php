<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use App\Validator\Constraints as Constraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_projects",
 *     indexes={
 *          @ORM\Index(columns={"customer_id","visible","name"}),
 *          @ORM\Index(columns={"customer_id","visible","id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 * @Constraints\Project
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\VirtualProperty(
 *      "CustomerName",
 *      exp="object.getCustomer() === null ? null : object.getCustomer().getName()",
 *      options={
 *          @Serializer\SerializedName("parentTitle"),
 *          @Serializer\Type(name="string"),
 *          @Serializer\Groups({"Project"})
 *      }
 * )
 * @Serializer\VirtualProperty(
 *      "CustomerAsId",
 *      exp="object.getCustomer() === null ? null : object.getCustomer().getId()",
 *      options={
 *          @Serializer\SerializedName("customer"),
 *          @Serializer\Type(name="integer"),
 *          @Serializer\Groups({"Project", "Team", "Not_Expanded"})
 *      }
 * )
 *
 * @Exporter\Order({"id", "name", "customer", "orderNumber", "orderDate", "start", "end", "budget", "timeBudget", "color", "visible", "teams", "comment"})
 * @Exporter\Expose("customer", label="label.customer", exp="object.getCustomer() === null ? null : object.getCustomer().getName()")
 * @ Exporter\Expose("teams", label="label.team", exp="object.getTeams().toArray()", type="array")
 */
class Project implements EntityWithMetaFields
{
    /**
     * Internal ID
     *
     * @var int|null
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.id", type="integer")
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * Customer for this project
     *
     * @var Customer
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Subresource", "Expanded"})
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Customer"))
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $customer;
    /**
     * Project name
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.name")
     *
     * @ORM\Column(name="name", type="string", length=150, nullable=false)
     * @Assert\NotNull()
     * @Assert\Length(min=2, max=150, allowEmptyString=false)
     */
    private $name;
    /**
     * Project order number
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project_Entity"})
     *
     * @Exporter\Expose(label="label.orderNumber")
     *
     * @ORM\Column(name="order_number", type="text", length=20, nullable=true)
     * @Assert\Length(max=20)
     */
    private $orderNumber;
    /**
     * @var \DateTime
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project_Entity"})
     * @Serializer\Type(name="DateTime")
     *
     * @Exporter\Expose(label="label.orderDate", type="datetime")
     *
     * @ORM\Column(name="order_date", type="datetime", nullable=true)
     */
    private $orderDate;
    /**
     * @var \DateTime
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project"})
     * @Serializer\Type(name="DateTime")
     *
     * @Exporter\Expose(label="label.project_start", type="datetime")
     *
     * @ORM\Column(name="start", type="datetime", nullable=true)
     */
    private $start;
    /**
     * @var \DateTime
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project"})
     * @Serializer\Type(name="DateTime")
     *
     * @Exporter\Expose(label="label.project_end", type="datetime")
     *
     * @ORM\Column(name="end", type="datetime", nullable=true)
     */
    private $end;
    /**
     * @var string
     * @internal used for storing the timezone for "order", "start" and "end" date
     *
     * @ORM\Column(name="timezone", type="string", length=64, nullable=true)
     */
    private $timezone;
    /**
     * @var bool
     * @internal used for having the localization state of the dates (see $timezone)
     */
    private $localized = false;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project_Entity"})
     *
     * @Exporter\Expose(label="label.comment")
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;
    /**
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.visible", type="boolean")
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $visible = true;

    // keep the trait include exactly here, for placing the column at the correct position
    use ColorTrait;

    /**
     * The total monetary budget, will be zero if not configured.
     *
     * @var float
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project_Entity"})
     *
     * @ Exporter\Expose(label="label.budget")
     *
     * @ORM\Column(name="budget", type="float", nullable=false)
     * @Assert\NotNull()
     */
    private $budget = 0.00;
    /**
     * The time budget in seconds, will be be zero if not configured.
     *
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project_Entity"})
     *
     * @ Exporter\Expose(label="label.timeBudget", type="duration")
     *
     * @ORM\Column(name="time_budget", type="integer", nullable=false)
     * @Assert\NotNull()
     */
    private $timeBudget = 0;
    /**
     * Meta fields
     *
     * All visible meta (custom) fields registered with this project
     *
     * @var ProjectMeta[]|Collection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project"})
     * @Serializer\Type(name="array<App\Entity\ProjectMeta>")
     * @Serializer\SerializedName("metaFields")
     * @Serializer\Accessor(getter="getVisibleMetaFields")
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ProjectMeta", mappedBy="project", cascade={"persist"})
     */
    private $meta;
    /**
     * Teams
     *
     * If no team is assigned, everyone can access the project (also depends on the teams of the customer)
     *
     * @var Team[]|ArrayCollection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Project"})
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Team"))
     *
     * @ORM\ManyToMany(targetEntity="Team", cascade={"persist"}, inversedBy="projects")
     * @ORM\JoinTable(
     *  name="kimai2_projects_teams",
     *  joinColumns={
     *      @ORM\JoinColumn(name="project_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="team_id", referencedColumnName="id", onDelete="CASCADE")
     *  }
     * )
     */
    private $teams;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->teams = new ArrayCollection();
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

    public function setComment(?string $comment): Project
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

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): Project
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * Make sure begin and end date have the correct timezone.
     * This will be called once for each item after being loaded from the database.
     */
    protected function localizeDates()
    {
        if ($this->localized) {
            return;
        }

        if (null === $this->timezone) {
            $this->timezone = date_default_timezone_get();
        }

        $timezone = new \DateTimeZone($this->timezone);

        if (null !== $this->orderDate) {
            $this->orderDate->setTimeZone($timezone);
        }

        if (null !== $this->start) {
            $this->start->setTimeZone($timezone);
        }

        if (null !== $this->end) {
            $this->end->setTimeZone($timezone);
        }

        $this->localized = true;
    }

    public function getOrderDate(): ?\DateTime
    {
        $this->localizeDates();

        return $this->orderDate;
    }

    public function setOrderDate(?\DateTime $orderDate): Project
    {
        $this->orderDate = $orderDate;

        if (null !== $orderDate) {
            $this->timezone = $orderDate->getTimezone()->getName();
        }

        return $this;
    }

    public function getStart(): ?\DateTime
    {
        $this->localizeDates();

        return $this->start;
    }

    public function setStart(?\DateTime $start): Project
    {
        $this->start = $start;

        if (null !== $start) {
            $this->timezone = $start->getTimezone()->getName();
        }

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        $this->localizeDates();

        return $this->end;
    }

    public function setEnd(?\DateTime $end): Project
    {
        $this->end = $end;

        if (null !== $end) {
            $this->timezone = $end->getTimezone()->getName();
        }

        return $this;
    }

    public function setBudget(float $budget): Project
    {
        $this->budget = $budget;

        return $this;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function setTimeBudget(int $seconds): Project
    {
        $this->timeBudget = $seconds;

        return $this;
    }

    public function getTimeBudget(): int
    {
        return $this->timeBudget;
    }

    /**
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

    public function addTeam(Team $team)
    {
        if ($this->teams->contains($team)) {
            return;
        }

        $this->teams->add($team);
        $team->addProject($this);
    }

    public function removeTeam(Team $team)
    {
        if (!$this->teams->contains($team)) {
            return;
        }
        $this->teams->removeElement($team);
        $team->removeProject($this);
    }

    /**
     * @return Collection<Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
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
            $this->teams = new ArrayCollection();
            $this->meta = new ArrayCollection();
        }
    }
}
