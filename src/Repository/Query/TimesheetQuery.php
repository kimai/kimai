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

/**
 * Can be used for advanced timesheet repository queries.
 */
class TimesheetQuery extends ActivityQuery
{
    public const STATE_ALL = 1;
    public const STATE_RUNNING = 2;
    public const STATE_STOPPED = 3;
    public const STATE_EXPORTED = 4;
    public const STATE_NOT_EXPORTED = 5;

    public const TIMESHEET_ORDER_ALLOWED = ['begin', 'end', 'duration', 'rate', 'customer', 'project', 'activity', 'description'];

    /**
     * @var User|null
     */
    protected $timesheetUser;
    /**
     * @var array
     */
    private $activities = [];
    /**
     * @var int
     */
    protected $state = self::STATE_ALL;
    /**
     * @var int
     */
    protected $exported = self::STATE_ALL;
    /**
     * @var DateRange
     */
    protected $dateRange;
    /**
     * @var iterable
     */
    protected $tags = [];
    /**
     * @var User[]
     */
    private $users = [];

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults([
            'order' => self::ORDER_DESC,
            'orderBy' => 'begin',
            'dateRange' => new DateRange()
        ]);
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
     * Limit the data exclusively to the user (eg. users own timesheets).
     *
     * @return User|int|null
     */
    public function getUser()
    {
        return $this->timesheetUser;
    }

    /**
     * Limit the data exclusively to the user (eg. users own timesheets).
     *
     * @param User|int|null $user
     * @return TimesheetQuery
     */
    public function setUser($user = null)
    {
        $this->timesheetUser = $user;

        return $this;
    }

    /**
     * @return Activity|int|null
     * @deprecated since 1.9 - use getProjects() instead - will be removed with 2.0
     */
    public function getActivity()
    {
        if (\count($this->activities) > 0) {
            return $this->activities[0];
        }

        return null;
    }

    public function getActivities(): array
    {
        return $this->activities;
    }

    /**
     * @param Activity|int|null $activity
     * @return $this
     * @deprecated since 1.9 - use setActivities() or addActivity() instead - will be removed with 2.0
     */
    public function setActivity($activity)
    {
        if (null === $activity) {
            $this->activities = [];
        } else {
            $this->activities = [$activity];
        }

        return $this;
    }

    /**
     * @param Activity|int $activity
     * @return $this
     */
    public function addActivity($activity)
    {
        $this->activities[] = $activity;

        return $this;
    }

    /**
     * @param Activity[]|int[] $activities
     * @return $this
     */
    public function setActivities(array $activities)
    {
        $this->activities = $activities;

        return $this;
    }

    public function hasActivities(): bool
    {
        return !empty($this->activities);
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     * @return TimesheetQuery
     */
    public function setState($state)
    {
        $state = (int) $state;
        if (\in_array($state, [self::STATE_ALL, self::STATE_RUNNING, self::STATE_STOPPED], true)) {
            $this->state = $state;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getExported()
    {
        return $this->exported;
    }

    /**
     * @param int $exported
     * @return TimesheetQuery
     */
    public function setExported($exported)
    {
        $exported = (int) $exported;
        if (\in_array($exported, [self::STATE_ALL, self::STATE_EXPORTED, self::STATE_NOT_EXPORTED], true)) {
            $this->exported = $exported;
        }

        return $this;
    }

    public function getBegin(): ?\DateTime
    {
        return $this->dateRange->getBegin();
    }

    /**
     * @param \DateTime $begin
     * @return TimesheetQuery
     */
    public function setBegin($begin)
    {
        $this->dateRange->setBegin($begin);

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->dateRange->getEnd();
    }

    /**
     * @param \DateTime $end
     * @return TimesheetQuery
     */
    public function setEnd($end)
    {
        $this->dateRange->setEnd($end);

        return $this;
    }

    /**
     * @return DateRange
     */
    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    /**
     * @param DateRange $dateRange
     * @return TimesheetQuery
     */
    public function setDateRange(DateRange $dateRange)
    {
        $this->dateRange = $dateRange;

        return $this;
    }

    /**
     * @return iterable
     */
    public function getTags($allowUnknown = false)
    {
        if (empty($this->tags)) {
            return [];
        }

        $result = [];

        foreach ($this->tags as $tag) {
            if (!$allowUnknown && $tag instanceof Tag && null === $tag->getId()) {
                continue;
            }
            $result[] = $tag;
        }

        return $result;
    }

    /**
     * @param iterable $tags
     * @return $this
     */
    public function setTags(iterable $tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
