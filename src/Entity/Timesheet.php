<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\ExportItemInterface;
use App\Validator\Constraints as Constraints;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_timesheet",
 *     indexes={
 *          @ORM\Index(columns={"user"}),
 *          @ORM\Index(columns={"activity_id"}),
 *          @ORM\Index(columns={"user","start_time"}),
 *          @ORM\Index(columns={"start_time"}),
 *          @ORM\Index(columns={"start_time","end_time"}),
 *          @ORM\Index(columns={"start_time","end_time","user"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\TimesheetRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Constraints\Timesheet
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\VirtualProperty(
 *      "ActivityAsId",
 *      exp="object.getActivity() === null ? null : object.getActivity().getId()",
 *      options={
 *          @Serializer\SerializedName("activity"),
 *          @Serializer\Type(name="integer"),
 *          @Serializer\Groups({"Not_Expanded"})
 *      }
 * )
 * @Serializer\VirtualProperty(
 *      "ProjectAsId",
 *      exp="object.getProject() === null ? null : object.getProject().getId()",
 *      options={
 *          @Serializer\SerializedName("project"),
 *          @Serializer\Type(name="integer"),
 *          @Serializer\Groups({"Not_Expanded"})
 *      }
 * )
 * @Serializer\VirtualProperty(
 *      "UserAsId",
 *      exp="object.getUser().getId()",
 *      options={
 *          @Serializer\SerializedName("user"),
 *          @Serializer\Type(name="integer"),
 *          @Serializer\Groups({"Default"})
 *      }
 * )
 * @Serializer\VirtualProperty(
 *      "TagsAsArray",
 *      exp="object.getTagsAsArray()",
 *      options={
 *          @Serializer\SerializedName("tags"),
 *          @Serializer\Type(name="array<string>"),
 *          @Serializer\Groups({"Default"})
 *      }
 * )
 */
class Timesheet implements EntityWithMetaFields, ExportItemInterface
{
    /**
     * Category: Normal work-time (default category)
     */
    public const WORK = 'work';
    /**
     * Category: Holiday
     */
    public const HOLIDAY = 'holiday';
    /**
     * Category: Sickness
     */
    public const SICKNESS = 'sickness';
    /**
     * Category: Parental leave
     */
    public const PARENTAL = 'parental';
    /**
     * Category: Overtime reduction
     */
    public const OVERTIME = 'overtime';

    /**
     * @var int|null
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var DateTime
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="DateTime")
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=false)
     * @Assert\NotNull()
     */
    private $begin;
    /**
     * @var DateTime
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="DateTime")
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    private $end;
    /**
     * @var string
     * @internal for storing the timezone of "begin" and "end" date
     *
     * @ORM\Column(name="timezone", type="string", length=64, nullable=false)
     */
    private $timezone;
    /**
     * @var bool
     * @internal for storing the localized state of dates (see $timezone)
     */
    private $localized = false;
    /**
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $duration = 0;
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="`user`", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $user;
    /**
     * Activity
     *
     * @var Activity
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Subresource", "Expanded"})
     * @SWG\Property(ref="#/definitions/ActivityExpanded")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Activity")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $activity;
    /**
     * Project
     *
     * @var Project
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Subresource", "Expanded"})
     * @SWG\Property(ref="#/definitions/ProjectExpanded")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $project;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;
    /**
     * @var float
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="rate", type="float", nullable=false)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $rate = 0.00;
    /**
     * @var float|null
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="internal_rate", type="float", nullable=true)
     */
    private $internalRate;
    /**
     * @var float|null
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Entity"})
     *
     * @ORM\Column(name="fixed_rate", type="float", nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $fixedRate = null;
    /**
     * @var float
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Entity"})
     *
     * @ORM\Column(name="hourly_rate", type="float", nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $hourlyRate = null;
    /**
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Entity"})
     *
     * @ORM\Column(name="exported", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $exported = false;
    /**
     * @var bool
     *
     * @ORM\Column(name="billable", type="boolean", nullable=false, options={"default": true})
     * @Assert\NotNull()
     */
    private $billable = true;
    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=10, nullable=false, options={"default": "work"})
     * @Assert\NotNull()
     */
    private $category = self::WORK;
    /**
     * @var DateTime|null
     * @internal used for limiting queries, eg. via API sync
     *
     * @Gedmo\Timestampable
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;
    /**
     * Tags
     *
     * @var Tag[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Tag", inversedBy="timesheets", cascade={"persist"})
     * @ORM\JoinTable(
     *  name="kimai2_timesheet_tags",
     *  joinColumns={
     *      @ORM\JoinColumn(name="timesheet_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")
     *  }
     * )
     * @Assert\Valid()
     */
    private $tags;
    /**
     * Meta fields
     *
     * All visible meta (custom) fields registered with this timesheet
     *
     * @var TimesheetMeta[]|Collection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Timesheet"})
     * @Serializer\Type(name="array<App\Entity\TimesheetMeta>")
     * @Serializer\SerializedName("metaFields")
     * @Serializer\Accessor(getter="getVisibleMetaFields")
     *
     * @ORM\OneToMany(targetEntity="App\Entity\TimesheetMeta", mappedBy="timesheet", cascade={"persist"})
     */
    private $meta;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->meta = new ArrayCollection();
    }

    /**
     * Get entry id, returns null for new entities which were not persisted.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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

        if (null !== $this->begin) {
            $this->begin->setTimeZone(new DateTimeZone($this->timezone));
        }

        if (null !== $this->end) {
            $this->end->setTimeZone(new DateTimeZone($this->timezone));
        }

        $this->localized = true;
    }

    public function getBegin(): ?DateTime
    {
        $this->localizeDates();

        return $this->begin;
    }

    /**
     * @param DateTime $begin
     * @return Timesheet
     */
    public function setBegin(DateTime $begin): Timesheet
    {
        $this->begin = $begin;
        $this->timezone = $begin->getTimezone()->getName();

        return $this;
    }

    public function getEnd(): ?DateTime
    {
        $this->localizeDates();

        return $this->end;
    }

    /**
     * @param DateTime $end
     * @return Timesheet
     */
    public function setEnd(?DateTime $end): Timesheet
    {
        $this->end = $end;

        if (null === $end) {
            $this->duration = 0;
            $this->rate = 0.00;
        } else {
            $this->timezone = $end->getTimezone()->getName();
        }

        return $this;
    }

    /**
     * @param int|null $duration
     * @return Timesheet
     */
    public function setDuration(?int $duration): Timesheet
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Do not rely on the results of this method for running records.
     *
     * @return int|null
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @param User $user
     * @return Timesheet
     */
    public function setUser(User $user): Timesheet
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param Activity $activity
     * @return Timesheet
     */
    public function setActivity($activity): Timesheet
    {
        $this->activity = $activity;

        return $this;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return Timesheet
     */
    public function setProject(Project $project): Timesheet
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @param string $description
     * @return Timesheet
     */
    public function setDescription($description): Timesheet
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param float $rate
     * @return Timesheet
     */
    public function setRate($rate): Timesheet
    {
        $this->rate = $rate;

        return $this;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setInternalRate(?float $rate): Timesheet
    {
        $this->internalRate = $rate;

        return $this;
    }

    public function getInternalRate(): ?float
    {
        return $this->internalRate;
    }

    /**
     * @param Tag $tag
     * @return Timesheet
     */
    public function addTag(Tag $tag): Timesheet
    {
        if ($this->tags->contains($tag)) {
            return $this;
        }
        $this->tags->add($tag);
        $tag->addTimesheet($this);

        return $this;
    }

    /**
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        if (!$this->tags->contains($tag)) {
            return;
        }
        $this->tags->removeElement($tag);
        $tag->removeTimesheet($this);
    }

    /**
     * @return Collection<Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @return string[]
     */
    public function getTagsAsArray(): array
    {
        return array_map(
            function (Tag $element) {
                return $element->getName();
            },
            $this->getTags()->toArray()
        );
    }

    /**
     * @return bool
     */
    public function isExported(): bool
    {
        return $this->exported;
    }

    /**
     * @param bool $exported
     * @return Timesheet
     */
    public function setExported(bool $exported): Timesheet
    {
        $this->exported = $exported;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * BE WARNED: this method should NOT be used.
     * It was ONLY introduced for the command "kimai:import-v1".
     *
     * @internal
     * @param string $timezone
     * @return Timesheet
     */
    public function setTimezone(string $timezone): Timesheet
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * This method returns ALWAYS: "timesheet"
     *
     * @return string
     */
    public function getType(): string
    {
        return 'timesheet';
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): Timesheet
    {
        $allowed = [self::WORK, self::HOLIDAY, self::SICKNESS, self::PARENTAL, self::OVERTIME];

        if (!\in_array($category, $allowed)) {
            throw new \InvalidArgumentException(sprintf('Invalid timesheet category "%s" given, expected one of: %s', $category, implode(', ', $allowed)));
        }

        $this->category = $category;

        return $this;
    }

    public function isBillable(): bool
    {
        return $this->billable;
    }

    public function setBillable(bool $billable): Timesheet
    {
        $this->billable = $billable;

        return $this;
    }

    public function getFixedRate(): ?float
    {
        return $this->fixedRate;
    }

    public function setFixedRate(?float $fixedRate): Timesheet
    {
        $this->fixedRate = $fixedRate;

        return $this;
    }

    public function getHourlyRate(): ?float
    {
        return $this->hourlyRate;
    }

    public function setHourlyRate(?float $hourlyRate): Timesheet
    {
        $this->hourlyRate = $hourlyRate;

        return $this;
    }

    public function getModifiedAt(): ?DateTime
    {
        return $this->modifiedAt;
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

    public function createCopy(?Timesheet $timesheet = null): Timesheet
    {
        if (null === $timesheet) {
            $timesheet = new Timesheet();
        }

        $values = get_object_vars($this);
        foreach ($values as $k => $v) {
            $timesheet->$k = $v;
        }

        $timesheet->meta = new ArrayCollection();

        /** @var TimesheetMeta $meta */
        foreach ($this->meta as $meta) {
            $timesheet->setMetaField(clone $meta);
        }

        return $timesheet;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->exported = false;
        }
    }
}
