<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Doctrine\ModifiedAt;
use App\Validator\Constraints as Constraints;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Internal docs:
 * - IDX_4F60C6B1415614018D93D649 (for ticktac in v1)
 */
#[ORM\Table(name: 'kimai2_timesheet')]
#[ORM\Index(columns: ['user'], name: 'IDX_4F60C6B18D93D649')]
#[ORM\Index(columns: ['activity_id'], name: 'IDX_4F60C6B181C06096')]
#[ORM\Index(columns: ['user', 'start_time'], name: 'IDX_4F60C6B18D93D649502DF587')]
#[ORM\Index(columns: ['start_time'], name: 'IDX_4F60C6B1502DF587')]
#[ORM\Index(columns: ['start_time', 'end_time'], name: 'IDX_4F60C6B1502DF58741561401')]
#[ORM\Index(columns: ['start_time', 'end_time', 'user'], name: 'IDX_4F60C6B1502DF587415614018D93D649')]
#[ORM\Index(columns: ['end_time', 'user'], name: 'IDX_4F60C6B1415614018D93D649')]
#[ORM\Index(columns: ['date_tz', 'user'], name: 'IDX_4F60C6B1BDF467148D93D649')]
#[ORM\Index(columns: ['end_time', 'user', 'start_time'], name: 'IDX_TIMESHEET_TICKTAC')]
#[ORM\Index(columns: ['user', 'project_id', 'activity_id'], name: 'IDX_TIMESHEET_RECENT_ACTIVITIES')]
#[ORM\Index(columns: ['user', 'id', 'duration'], name: 'IDX_TIMESHEET_RESULT_STATS')]
#[ORM\Entity(repositoryClass: 'App\Repository\TimesheetRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[ORM\HasLifecycleCallbacks]
#[Serializer\ExclusionPolicy('all')]
#[Serializer\VirtualProperty('ActivityAsId', exp: 'object.getActivity() === null ? null : object.getActivity().getId()', options: [new Serializer\SerializedName('activity'), new Serializer\Type(name: 'integer'), new Serializer\Groups(['Not_Expanded'])])]
#[Serializer\VirtualProperty('ProjectAsId', exp: 'object.getProject() === null ? null : object.getProject().getId()', options: [new Serializer\SerializedName('project'), new Serializer\Type(name: 'integer'), new Serializer\Groups(['Not_Expanded'])])]
#[Serializer\VirtualProperty('UserAsId', exp: 'object.getUser().getId()', options: [new Serializer\SerializedName('user'), new Serializer\Type(name: 'integer'), new Serializer\Groups(['Not_Expanded'])])]
#[Serializer\VirtualProperty('TagsAsArray', exp: 'object.getTagsAsArray()', options: [new Serializer\SerializedName('tags'), new Serializer\Type(name: 'array<string>'), new Serializer\Groups(['Default'])])]
#[Constraints\Timesheet]
#[Constraints\TimesheetDeactivated]
class Timesheet implements EntityWithMetaFields, ExportableItem, ModifiedAt
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

    public const BILLABLE_AUTOMATIC = 'auto';
    public const BILLABLE_YES = 'yes';
    public const BILLABLE_NO = 'no';
    public const BILLABLE_DEFAULT = 'default';

    /**
     * Unique Timesheet ID
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?int $id = null;
    /**
     * Reflects the date in the user timezone (not in UTC).
     * This value is automatically set through the begin column and ONLY used in statistic queries.
     */
    #[ORM\Column(name: 'date_tz', type: 'date_immutable', nullable: false)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $date = null;
    /**
     * Time records start date-time.
     *
     * Attention: Accessor MUST be used, otherwise date will be serialized in UTC.
     */
    #[ORM\Column(name: 'start_time', type: 'datetime', nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'DateTime')]
    #[Serializer\Accessor(getter: 'getBegin')]
    private ?DateTime $begin = null;
    /**
     * Time records end date-time.
     *
     * Attention: Accessor MUST be used, otherwise date will be serialized in UTC.
     */
    #[ORM\Column(name: 'end_time', type: 'datetime', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'DateTime')]
    #[Serializer\Accessor(getter: 'getEnd')]
    private ?\DateTime $end = null;
    /**
     * @internal for storing the timezone of "begin" and "end" date
     */
    #[ORM\Column(name: 'timezone', type: 'string', length: 64, nullable: false)]
    #[Assert\Timezone]
    private ?string $timezone = null;
    /**
     * @internal for storing the localized state of dates (see $timezone)
     */
    private bool $localized = false;
    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?int $duration = 0;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: '`user`', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Subresource', 'Expanded'])]
    #[OA\Property(ref: '#/components/schemas/User')]
    private ?User $user = null;
    #[ORM\ManyToOne(targetEntity: Activity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Subresource', 'Expanded'])]
    #[OA\Property(ref: '#/components/schemas/ActivityExpanded')]
    private ?Activity $activity = null;
    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Subresource', 'Expanded'])]
    #[OA\Property(ref: '#/components/schemas/ProjectExpanded')]
    private ?Project $project = null;
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $description = null;
    #[ORM\Column(name: 'rate', type: 'float', nullable: false)]
    #[Assert\GreaterThanOrEqual(0)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private float $rate = 0.00;
    #[ORM\Column(name: 'internal_rate', type: 'float', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?float $internalRate = null;
    #[ORM\Column(name: 'fixed_rate', type: 'float', nullable: true)]
    #[Assert\GreaterThanOrEqual(0)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Entity'])]
    private ?float $fixedRate = null;
    #[ORM\Column(name: 'hourly_rate', type: 'float', nullable: true)]
    #[Assert\GreaterThanOrEqual(0)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Entity'])]
    private ?float $hourlyRate = null;
    #[ORM\Column(name: 'exported', type: 'boolean', nullable: false, options: ['default' => false])]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private bool $exported = false;
    #[ORM\Column(name: 'billable', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private bool $billable = true;
    /**
     * Internal property used to determine whether the billable field should be calculated automatically.
     */
    #[Assert\NotNull]
    private ?string $billableMode = self::BILLABLE_DEFAULT;
    #[ORM\Column(name: 'category', type: 'string', length: 10, nullable: false, options: ['default' => 'work'])]
    #[Assert\NotNull]
    private ?string $category = self::WORK;
    #[ORM\Column(name: 'modified_at', type: 'datetime_immutable', nullable: true)]
    private \DateTimeImmutable $modifiedAt;
    /**
     * Tags
     *
     * @var Collection<Tag>
     */
    #[ORM\JoinTable(name: 'kimai2_timesheet_tags')]
    #[ORM\JoinColumn(name: 'timesheet_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'timesheets', cascade: ['persist'])]
    #[Assert\Valid]
    private Collection $tags;
    /**
     * Meta fields registered with the timesheet
     *
     * @var Collection<TimesheetMeta>
     */
    #[ORM\OneToMany(mappedBy: 'timesheet', targetEntity: TimesheetMeta::class, cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Timesheet'])]
    #[Serializer\Type(name: 'array<App\Entity\TimesheetMeta>')]
    #[Serializer\SerializedName('metaFields')]
    #[Serializer\Accessor(getter: 'getVisibleMetaFields')]
    private Collection $meta;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->meta = new ArrayCollection();
        $this->modifiedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
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
    protected function localizeDates(): void
    {
        if ($this->localized) {
            return;
        }

        if (null !== $this->begin) {
            $this->begin->setTimezone(new DateTimeZone($this->timezone));
        }

        if (null !== $this->end) {
            $this->end->setTimezone(new DateTimeZone($this->timezone));
        }

        $this->localized = true;
    }

    public function getBegin(): ?DateTime
    {
        $this->localizeDates();

        return $this->begin;
    }

    public function setBegin(DateTime $begin): Timesheet
    {
        $this->begin = $begin;
        $this->timezone = $begin->getTimezone()->getName();
        // make sure that the original date is always kept in UTC
        $this->date = new \DateTimeImmutable($begin->format('Y-m-d 00:00:00'), new DateTimeZone('UTC'));

        return $this;
    }

    public function getEnd(): ?DateTime
    {
        $this->localizeDates();

        return $this->end;
    }

    public function isRunning(): bool
    {
        return $this->end === null;
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
     * @param bool $calculate
     * @return int|null
     */
    public function getDuration(bool $calculate = true): ?int
    {
        // only auto calculate if manually set duration is null - the result is important for eg. validations
        if ($calculate && $this->duration === null && $this->begin !== null && $this->end !== null) {
            return $this->getCalculatedDuration();
        }

        return $this->duration;
    }

    public function getCalculatedDuration(): ?int
    {
        if ($this->begin !== null && $this->end !== null) {
            return $this->end->getTimestamp() - $this->begin->getTimestamp();
        }

        return null;
    }

    public function setUser(?User $user): Timesheet
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setActivity(?Activity $activity): Timesheet
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

    public function setProject(?Project $project): Timesheet
    {
        $this->project = $project;

        return $this;
    }

    public function setDescription(?string $description): Timesheet
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

    public function addTag(Tag $tag): Timesheet
    {
        if ($this->tags->contains($tag)) {
            return $this;
        }
        $this->tags->add($tag);

        return $this;
    }

    public function removeTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            return;
        }
        $this->tags->removeElement($tag);
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
        /** @var array<Tag> $tags */
        $tags = $this->getTags()->toArray();

        return array_map(
            function ($element) {
                return (string) $element->getName();
            },
            $tags
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
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * BE WARNED: this method should NOT be used from outside.
     * It is reserved for some very rare use-cases.
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

    public function getAmount(): float
    {
        return 1.0;
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

    public function getBillable(): bool
    {
        return $this->billable;
    }

    public function setBillable(bool $billable): Timesheet
    {
        $this->billable = $billable;

        return $this;
    }

    public function getBillableMode(): ?string
    {
        return $this->billableMode;
    }

    public function setBillableMode(?string $billableMode): void
    {
        $this->billableMode = $billableMode;
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

    public function getModifiedAt(): \DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTimeImmutable $dateTime): void
    {
        $this->modifiedAt = $dateTime;
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

    public function resetRates(): void
    {
        $this->setRate(0.00);
        $this->setInternalRate(null);
        $this->setHourlyRate(null);
        $this->setFixedRate(null);
        $this->setBillableMode(Timesheet::BILLABLE_AUTOMATIC);
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

        $timesheet->tags = new ArrayCollection();

        /** @var Tag $tag */
        foreach ($this->tags as $tag) {
            $timesheet->addTag($tag);
        }

        return $timesheet;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }

        // field will not be set, if it contains a value
        $this->modifiedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->exported = false;

        $currentMeta = $this->meta;
        $this->meta = new ArrayCollection();
        /** @var TimesheetMeta $meta */
        foreach ($currentMeta as $meta) {
            $newMeta = clone $meta;
            $newMeta->setEntity($this);
            $this->setMetaField($newMeta);
        }

        $currentTags = $this->tags;
        $this->tags = new ArrayCollection();
        /** @var Tag $tag */
        foreach ($currentTags as $tag) {
            $this->addTag($tag);
        }
    }
}
