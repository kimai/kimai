<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Timesheet entity.
 *
 * @ORM\Table(
 *     name="timesheet",
 *     indexes={
 *          @ORM\Index(columns={"user"}),
 *          @ORM\Index(columns={"activity_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\TimesheetRepository")
 * @ORM\HasLifecycleCallbacks()
 * @App\Validator\Constraints\Timesheet
 */
class Timesheet
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
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=false)
     * @Assert\NotNull()
     */
    private $begin;

    /**
     * @var \DateTime
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
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $user;

    /**
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Activity", inversedBy="timesheets")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $activity;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="timesheets")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="decimal", precision=10, scale=2, nullable=false)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $rate = 0.00;

    /**
     * @var float
     *
     * @ORM\Column(name="fixed_rate", type="decimal", precision=10, scale=2, nullable=true)
     * @Assert\GreaterThanOrEqual(0)
     */
    private $fixedRate = null;

    /**
     * @var float
     *
     * @ORM\Column(name="hourly_rate", type="decimal", precision=10, scale=2, nullable=true)
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
     * @var \App\Entity\Tag[]
     *
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="timesheets", cascade={"persist"})
     * @ORM\JoinTable(
     *  name="timesheet_tags",
     *  joinColumns={
     *      @ORM\JoinColumn(name="timesheet_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $tags;

    /**
     * Default constructor, initializes collections
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Get entry id
     *
     * @return int
     */
    public function getId()
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
            $this->begin->setTimeZone(new \DateTimeZone($this->timezone));
        }

        if (null !== $this->end) {
            $this->end->setTimeZone(new \DateTimeZone($this->timezone));
        }

        $this->localized = true;
    }

    /**
     * @return \DateTime
     */
    public function getBegin()
    {
        $this->localizeDates();

        return $this->begin;
    }

    /**
     * @param \DateTime $begin
     * @return Timesheet
     */
    public function setBegin(\DateTime $begin)
    {
        $this->begin = $begin;
        $this->timezone = $begin->getTimezone()->getName();

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEnd()
    {
        $this->localizeDates();

        return $this->end;
    }

    /**
     * @param \DateTime $end
     * @return Timesheet
     */
    public function setEnd(?\DateTime $end)
    {
        $this->end = $end;

        if (null === $end) {
            $this->duration = 0;
            $this->rate = 0;
        } else {
            $this->timezone = $end->getTimezone()->getName();
        }

        return $this;
    }

    /**
     * @param int $duration
     * @return Timesheet
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Do not rely on the results of this method for running records.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param User $user
     * @return Timesheet
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Activity $activity
     * @return Timesheet
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return Timesheet
     */
    public function setProject(Project $project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Timesheet
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set rate
     *
     * @param float $rate
     * @return Timesheet
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @return float
     */
    public function getFixedRate(): ?float
    {
        return $this->fixedRate;
    }

    /**
     * @param float $fixedRate
     * @return Timesheet
     */
    public function setFixedRate(?float $fixedRate)
    {
        $this->fixedRate = $fixedRate;

        return $this;
    }

    /**
     * @return float
     */
    public function getHourlyRate(): ?float
    {
        return $this->hourlyRate;
    }

    /**
     * @param float $hourlyRate
     * @return Timesheet
     */
    public function setHourlyRate(?float $hourlyRate)
    {
        $this->hourlyRate = $hourlyRate;

        return $this;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) {
            return;
        }
        $this->tags->add($tag);
        $tag->addTimesheet($this);
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
     * @return Tag[]|ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return string[]
     */
    public function getTagsAsArray()
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
    public function setExported(bool $exported)
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
     * BE WARNED: this method should NOT be used programmatically, there is very likely no reason for it!
     *
     * @deprecated since it was introduced, only meant for the initial migration. Will be removed with 1.0.
     * @param string $timezone
     * @return Timesheet
     */
    public function setTimezone(string $timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }
}
