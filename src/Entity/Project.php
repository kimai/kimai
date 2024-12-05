<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Doctrine\Behavior\CreatedAt;
use App\Doctrine\Behavior\CreatedTrait;
use App\Export\Annotation as Exporter;
use App\Repository\ProjectRepository;
use App\Validator\Constraints as Constraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_projects')]
#[ORM\Index(columns: ['customer_id', 'visible', 'name'])]
#[ORM\Index(columns: ['customer_id', 'visible', 'id'])]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
#[Serializer\VirtualProperty('CustomerName', exp: 'object.getCustomer() === null ? null : object.getCustomer().getName()', options: [new Serializer\SerializedName('parentTitle'), new Serializer\Type(name: 'string'), new Serializer\Groups(['Project'])])]
#[Serializer\VirtualProperty('CustomerAsId', exp: 'object.getCustomer() === null ? null : object.getCustomer().getId()', options: [new Serializer\SerializedName('customer'), new Serializer\Type(name: 'integer'), new Serializer\Groups(['Project', 'Team', 'Not_Expanded'])])]
#[Exporter\Order(['id', 'name', 'customer', 'orderNumber', 'orderDate', 'start', 'end', 'budget', 'timeBudget', 'budgetType', 'color', 'visible', 'comment', 'billable', 'number'])]
#[Exporter\Expose(name: 'customer', label: 'customer', exp: 'object.getCustomer() === null ? null : object.getCustomer().getName()')]
#[Constraints\Project]
class Project implements EntityWithMetaFields, EntityWithBudget, CreatedAt
{
    use BudgetTrait;
    use ColorTrait;
    use CreatedTrait;

    /**
     * Unique Project ID
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'id', type: 'integer')]
    private ?int $id = null;
    /**
     * Customer for this project
     */
    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Subresource', 'Expanded'])]
    #[OA\Property(ref: '#/components/schemas/Customer')]
    private ?Customer $customer = null;
    /**
     * Project name
     */
    #[ORM\Column(name: 'name', type: 'string', length: 150, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Length(min: 2, max: 150)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'name')]
    private ?string $name = null;
    /**
     * Project order number
     */
    #[ORM\Column(name: 'order_number', type: 'text', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Project_Entity'])]
    #[Exporter\Expose(label: 'orderNumber')]
    private ?string $orderNumber = null;
    /**
     * Order date for the project
     *
     * Attention: Accessor MUST be used, otherwise date will be serialized in UTC.
     */
    #[ORM\Column(name: 'order_date', type: 'datetime', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Project_Entity'])]
    #[Serializer\Type(name: "DateTime<'Y-m-d'>")]
    #[Serializer\Accessor(getter: 'getOrderDate')]
    #[Exporter\Expose(label: 'orderDate', type: 'datetime')]
    private ?\DateTime $orderDate = null;
    /**
     * Project start date (times before this date cannot be recorded)
     *
     * Attention: Accessor MUST be used, otherwise date will be serialized in UTC.
     */
    #[ORM\Column(name: 'start', type: 'datetime', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Project'])]
    #[Serializer\Type(name: "DateTime<'Y-m-d'>")]
    #[Serializer\Accessor(getter: 'getStart')]
    #[Exporter\Expose(label: 'project_start', type: 'datetime')]
    private ?\DateTime $start = null;
    /**
     * Project end time (times after this date cannot be recorded)
     *
     * Attention: Accessor MUST be used, otherwise date will be serialized in UTC.
     */
    #[ORM\Column(name: 'end', type: 'datetime', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Project'])]
    #[Serializer\Type(name: "DateTime<'Y-m-d'>")]
    #[Serializer\Accessor(getter: 'getEnd')]
    #[Exporter\Expose(label: 'project_end', type: 'datetime')]
    private ?\DateTime $end = null;
    #[ORM\Column(name: 'timezone', type: 'string', length: 64, nullable: true)]
    private ?string $timezone = null;
    private bool $localized = false;
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'comment')]
    private ?string $comment = null;
    /**
     * If the project is not visible, times cannot be recorded
     */
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'visible', type: 'boolean')]
    private bool $visible = true;
    #[ORM\Column(name: 'billable', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'billable', type: 'boolean')]
    private bool $billable = true;
    /**
     * Meta fields registered with the project
     *
     * @var Collection<ProjectMeta>
     */
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectMeta::class, cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Project'])]
    #[Serializer\Type(name: 'array<App\Entity\ProjectMeta>')]
    #[Serializer\SerializedName('metaFields')]
    #[Serializer\Accessor(getter: 'getVisibleMetaFields')]
    private Collection $meta;
    /**
     * Teams with access to the project
     *
     * @var Collection<Team>
     */
    #[ORM\JoinTable(name: 'kimai2_projects_teams')]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'team_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: Team::class, inversedBy: 'projects', cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Project'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Team'))]
    private Collection $teams;
    #[ORM\Column(name: 'invoice_text', type: 'text', nullable: true)]
    private ?string $invoiceText = null;
    /**
     * Whether this project allows booking of global activities
     */
    #[ORM\Column(name: 'global_activities', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private bool $globalActivities = true;
    #[ORM\Column(name: 'number', type: 'string', length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'project_number')]
    private ?string $number = null;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): Project
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

    public function setBillable(bool $billable): void
    {
        $this->billable = $billable;
    }

    public function isBillable(): bool
    {
        return $this->billable;
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
    protected function localizeDates(): void
    {
        if ($this->localized) {
            return;
        }

        if (null === $this->timezone) {
            $this->timezone = date_default_timezone_get();
        }

        $timezone = new \DateTimeZone($this->timezone);

        if (null !== $this->orderDate) {
            $this->orderDate->setTimezone($timezone);
        }

        if (null !== $this->start) {
            $this->start->setTimezone($timezone);
        }

        if (null !== $this->end) {
            $this->end->setTimezone($timezone);
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

    public function isGlobalActivities(): bool
    {
        return $this->globalActivities;
    }

    public function setGlobalActivities(bool $globalActivities): void
    {
        $this->globalActivities = $globalActivities;
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

    public function addTeam(Team $team): void
    {
        if ($this->teams->contains($team)) {
            return;
        }

        $this->teams->add($team);
        $team->addProject($this);
    }

    public function removeTeam(Team $team): void
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

    public function isVisibleAtDate(\DateTime $dateTime): bool
    {
        if (!$this->isVisible()) {
            return false;
        }
        if ($this->getCustomer() !== null && !$this->getCustomer()->isVisible()) {
            return false;
        }
        if ($this->getStart() !== null && $dateTime < $this->getStart()) {
            return false;
        }
        if ($this->getEnd() !== null && $dateTime > $this->getEnd()) {
            return false;
        }

        return true;
    }

    public function getInvoiceText(): ?string
    {
        return $this->invoiceText;
    }

    public function setInvoiceText(?string $invoiceText): void
    {
        $this->invoiceText = $invoiceText;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function __clone()
    {
        if ($this->id !== null) {
            $this->id = null;
        }

        $this->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        $currentTeams = $this->teams;
        $this->teams = new ArrayCollection();
        /** @var Team $team */
        foreach ($currentTeams as $team) {
            $this->addTeam($team);
        }

        $this->number = null;
        $currentMeta = $this->meta;
        $this->meta = new ArrayCollection();
        /** @var ProjectMeta $meta */
        foreach ($currentMeta as $meta) {
            $newMeta = clone $meta;
            $newMeta->setEntity($this);
            $this->setMetaField($newMeta);
        }
    }
}
