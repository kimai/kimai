<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Activity;
use App\Entity\Tag;
use App\Entity\User;
use App\Form\Model\DateRange;

class TimesheetQuery extends ActivityQuery implements BillableInterface
{
    use BillableTrait;
    use DateRangeTrait;

    public const STATE_ALL = 1;
    public const STATE_RUNNING = 2;
    public const STATE_STOPPED = 3;
    public const STATE_EXPORTED = 4;
    public const STATE_NOT_EXPORTED = 5;

    public const TIMESHEET_ORDER_ALLOWED = ['begin', 'end', 'duration', 'rate', 'hourlyRate', 'customer', 'project', 'activity', 'description'];

    private ?User $timesheetUser = null;
    /** @var array<Activity> */
    private array $activities = [];
    private int $state = self::STATE_ALL;
    private int $exported = self::STATE_ALL;
    private ?int $maxResults = null;
    private ?\DateTime $modifiedAfter = null;
    /**
     * @var array<Tag>
     */
    private array $tags = [];
    /**
     * @var array<User>
     */
    private array $users = [];

    public function __construct(bool $resetTimes = true)
    {
        parent::__construct();
        $this->setDefaults([
            'order' => self::ORDER_DESC,
            'orderBy' => 'begin',
            'dateRange' => new DateRange($resetTimes),
            'exported' => self::STATE_ALL,
            'state' => self::STATE_ALL,
            'billable' => null,
            'tags' => [],
            'users' => [],
            'activities' => [],
        ]);
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function setMaxResults(?int $maxResults): void
    {
        $this->maxResults = $maxResults;
    }

    public function addUser(User $user): self
    {
        $this->users[$user->getId()] = $user;

        return $this;
    }

    public function removeUser(User $user): self
    {
        if (isset($this->users[$user->getId()])) {
            unset($this->users[$user->getId()]);
        }

        return $this;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return array_values($this->users);
    }

    /**
     * Limit the data exclusively to the user.
     */
    public function getUser(): ?User
    {
        return $this->timesheetUser;
    }

    /**
     * Limit the data exclusively to the user.
     */
    public function setUser(?User $user): void
    {
        $this->timesheetUser = $user;
    }

    /**
     * @return array<int>
     */
    public function getActivityIds(): array
    {
        return array_values(array_filter(array_unique(array_map(function (Activity $activity) {
            return $activity->getId();
        }, $this->activities)), function ($id) {
            return $id !== null;
        }));
    }

    /**
     * @return array<Activity>
     */
    public function getActivities(): array
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): TimesheetQuery
    {
        $this->activities[] = $activity;

        return $this;
    }

    /**
     * @param array<Activity> $activities
     */
    public function setActivities(array $activities): TimesheetQuery
    {
        $this->activities = $activities;

        return $this;
    }

    public function hasActivities(): bool
    {
        return !empty($this->activities);
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function isRunning(): bool
    {
        return $this->state === self::STATE_RUNNING;
    }

    public function isStopped(): bool
    {
        return $this->state === self::STATE_STOPPED;
    }

    public function setState(int $state): TimesheetQuery
    {
        if (\in_array($state, [self::STATE_ALL, self::STATE_RUNNING, self::STATE_STOPPED], true)) {
            $this->state = $state;
        }

        return $this;
    }

    public function getExported(): int
    {
        return $this->exported;
    }

    public function isExported(): bool
    {
        return $this->exported === self::STATE_EXPORTED;
    }

    public function isNotExported(): bool
    {
        return $this->exported === self::STATE_NOT_EXPORTED;
    }

    public function setExported(int $exported): void
    {
        if (!\in_array($exported, [self::STATE_ALL, self::STATE_EXPORTED, self::STATE_NOT_EXPORTED], true)) {
            throw new \InvalidArgumentException('Unknown export state given');
        }

        $this->exported = $exported;
    }

    /**
     * @return array<Tag>
     */
    public function getTags(): array
    {
        return array_values($this->tags);
    }

    public function removeTag(Tag $tag): void
    {
        if (isset($this->tags[$tag->getId()])) {
            unset($this->tags[$tag->getId()]);
        }
    }

    public function addTag(Tag $tag): void
    {
        $this->tags[$tag->getId()] = $tag;
    }

    public function getModifiedAfter(): ?\DateTime
    {
        return $this->modifiedAfter;
    }

    public function setModifiedAfter(\DateTime $modifiedAfter): TimesheetQuery
    {
        $this->modifiedAfter = $modifiedAfter;

        return $this;
    }
}
