<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\ExportItemInterface;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
 * @App\Validator\Constraints\Timesheet
 *
 * columns={"user"}                         => IDX_4F60C6B18D93D649                 => count results for user timesheets
 * columns={"activity_id"}                  => IDX_4F60C6B181C06096                 => ???
 * columns={"user","start_time"}            => IDX_4F60C6B18D93D649502DF587         => recent activities, user timesheet with date filzer
 * columns={"start_time"}                   => IDX_4F60C6B1502DF587                 => team timesheets with timerange filter only
 * columns={"start_time","end_time"}        => IDX_4F60C6B1502DF58741561401         => ???
 * columns={"start_time","end_time","user"} => IDX_4F60C6B1502DF587415614018D93D649 => ???
 */
class Timesheet implements EntityWithMetaFields, ExportItemInterface
{
    public const TYPE_TIMESHEET = 'timesheet';
    public const CATEGORY_WORK = 'work';

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=false)
     * @Assert\NotNull()
     */
    private $begin;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    private $end;

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=64, nullable=false)
     */
    private $timezone;

    /**
     * @var bool
     */
    private $localized = false;

    /**
     * @var int
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
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Activity")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $activity;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", nullable=false)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $rate = 0.00;

    /**
     * @var float|null
     *
     * @ORM\Column(name="internal_rate", type="float", nullable=true)
     */
    private $internalRate;

    /**
     * @var float|null
     *
     * @ORM\Column(name="fixed_rate", type="float", nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $fixedRate = null;

    /**
     * @var float
     *
     * @ORM\Column(name="hourly_rate", type="float", nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $hourlyRate = null;

    /**
     * @var bool
     *
     * @ORM\Column(name="exported", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $exported = false;

    /**
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
     * @var TimesheetMeta[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\TimesheetMeta", mappedBy="timesheet", cascade={"persist"})
     */
    private $meta;

    /**
     * Default constructor, initializes collections
     */
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

    public function getType(): string
    {
        // this will be improved in a future version
        return self::TYPE_TIMESHEET;
    }

    public function getCategory(): string
    {
        // this will be improved in a future version
        return self::CATEGORY_WORK;
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

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->exported = false;
        }
    }
}
