<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_activities",
 *      indexes={
 *          @ORM\Index(columns={"visible","project_id"}),
 *          @ORM\Index(columns={"visible","project_id","name"}),
 *          @ORM\Index(columns={"visible","name"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\VirtualProperty(
 *      "ProjectName",
 *      exp="object.getProject() === null ? null : object.getProject().getName()",
 *      options={
 *          @Serializer\SerializedName("parentTitle"),
 *          @Serializer\Type(name="string"),
 *          @Serializer\Groups({"Activity"})
 *      }
 * )
 * @Serializer\VirtualProperty(
 *      "ProjectAsId",
 *      exp="object.getProject() === null ? null : object.getProject().getId()",
 *      options={
 *          @Serializer\SerializedName("project"),
 *          @Serializer\Type(name="integer"),
 *          @Serializer\Groups({"Activity", "Team", "Not_Expanded"})
 *      }
 * )
 *
 * @Exporter\Order({"id", "name", "project", "budget", "timeBudget", "budgetType", "color", "visible", "comment"})
 * @Exporter\Expose("project", label="label.project", exp="object.getProject() === null ? null : object.getProject().getName()")
 */
class Activity implements EntityWithMetaFields, EntityWithBudget
{
    use BudgetTrait;
    use ColorTrait;

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
     * @var Project|null
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Subresource", "Expanded"})
     * @SWG\Property(ref="#/definitions/Project")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $project;
    /**
     * Name of this activity
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.name")
     *
     * @ORM\Column(name="name", type="string", length=150, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=150, allowEmptyString=false)
     */
    private $name;
    /**
     * Description of this activity
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Activity_Entity"})
     *
     * @Exporter\Expose(label="label.comment")
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;
    /**
     * Whether this activity is visible and can be used for timesheets
     *
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.visible", type="boolean")
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false, options={"default": true})
     * @Assert\NotNull()
     */
    private $visible = true;
    /**
     * Meta fields
     *
     * All visible meta (custom) fields registered with this activity
     *
     * @var ActivityMeta[]|Collection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Activity"})
     * @Serializer\Type(name="array<App\Entity\ActivityMeta>")
     * @Serializer\SerializedName("metaFields")
     * @Serializer\Accessor(getter="getVisibleMetaFields")
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ActivityMeta", mappedBy="activity", cascade={"persist"})
     */
    private $meta;
    /**
     * Teams
     *
     * If no team is assigned, everyone can access the activity
     *
     * @var Team[]|ArrayCollection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Activity"})
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Team"))
     *
     * @ORM\ManyToMany(targetEntity="Team", cascade={"persist"}, inversedBy="activities")
     * @ORM\JoinTable(
     *  name="kimai2_activities_teams",
     *  joinColumns={
     *      @ORM\JoinColumn(name="activity_id", referencedColumnName="id", onDelete="CASCADE")
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
        $team->addActivity($this);
    }

    public function removeTeam(Team $team)
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
        }

        $currentTeams = $this->teams;
        $this->teams = new ArrayCollection();
        /** @var Team $team */
        foreach ($currentTeams as $team) {
            $this->addTeam($team);
        }

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
