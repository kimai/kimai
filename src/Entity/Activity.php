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
use App\Repository\ActivityRepository;
use App\Validator\Constraints as Constraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_activities')]
#[ORM\Index(columns: ['visible', 'project_id'])]
#[ORM\Index(columns: ['visible', 'project_id', 'name'])]
#[ORM\Index(columns: ['visible', 'name'])]
#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
#[Serializer\VirtualProperty('ProjectName', exp: 'object.getProject() === null ? null : object.getProject().getName()', options: [new Serializer\SerializedName('parentTitle'), new Serializer\Type(name: 'string'), new Serializer\Groups(['Activity'])])]
#[Serializer\VirtualProperty('ProjectAsId', exp: 'object.getProject() === null ? null : object.getProject().getId()', options: [new Serializer\SerializedName('project'), new Serializer\Type(name: 'integer'), new Serializer\Groups(['Activity', 'Team', 'Not_Expanded'])])]
#[Exporter\Order(['id', 'name', 'project', 'budget', 'timeBudget', 'budgetType', 'color', 'visible', 'comment', 'billable', 'number'])]
#[Exporter\Expose(name: 'project', label: 'project', exp: 'object.getProject() === null ? null : object.getProject().getName()')]
#[Constraints\Activity]
class Activity implements EntityWithMetaFields, EntityWithBudget, CreatedAt
{
    use BudgetTrait;
    use ColorTrait;
    use CreatedTrait;

    /**
     * Unique activity ID
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'id', type: 'integer')]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Subresource', 'Expanded'])]
    #[OA\Property(ref: '#/components/schemas/ProjectExpanded')]
    private ?Project $project = null;
    /**
     * Name of this activity
     */
    #[ORM\Column(name: 'name', type: 'string', length: 150, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 150)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'name')]
    private ?string $name = null;
    /**
     * Description of this activity
     */
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'comment')]
    private ?string $comment = null;
    /**
     * Whether this activity is visible and can be selected
     */
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false, options: ['default' => true])]
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
     * Meta fields registered with the activity
     *
     * @var Collection<ActivityMeta>
     */
    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivityMeta::class, cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Activity'])]
    #[Serializer\Type(name: 'array<App\Entity\ActivityMeta>')]
    #[Serializer\SerializedName('metaFields')]
    #[Serializer\Accessor(getter: 'getVisibleMetaFields')]
    private Collection $meta;
    /**
     * Teams with access to the activity
     *
     * @var Collection<Team>
     */
    #[ORM\JoinTable(name: 'kimai2_activities_teams')]
    #[ORM\JoinColumn(name: 'activity_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'team_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: Team::class, inversedBy: 'activities', cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Activity'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Team'))]
    private Collection $teams;
    #[ORM\Column(name: 'invoice_text', type: 'text', nullable: true)]
    private ?string $invoiceText = null;
    #[ORM\Column(name: 'number', type: 'string', length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'activity_number')]
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

    public function setBillable(bool $billable): void
    {
        $this->billable = $billable;
    }

    public function isBillable(): bool
    {
        return $this->billable;
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
        $team->addActivity($this);
    }

    public function removeTeam(Team $team): void
    {
        if (!$this->teams->contains($team)) {
            return;
        }
        $this->teams->removeElement($team);
        $team->removeActivity($this);
    }

    /**
     * @return Collection<Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
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
        /** @var ActivityMeta $meta */
        foreach ($currentMeta as $meta) {
            $newMeta = clone $meta;
            $newMeta->setEntity($this);
            $this->setMetaField($newMeta);
        }
    }
}
