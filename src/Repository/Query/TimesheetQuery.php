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

    /**
     * Overwritten for different default order
     * @var string
     */
    protected $order = self::ORDER_DESC;
    /**
     * Overwritten for different default order
     * @var string
     */
    protected $orderBy = 'begin';
    /**
     * @var User|null
     */
    protected $user;
    /**
     * @var Activity|null
     */
    protected $activity;
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

    public function __construct()
    {
        $this->dateRange = new DateRange();
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|int|null $user
     * @return TimesheetQuery
     */
    public function setUser($user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Activity overwrites: setProject() and setCustomer()
     *
     * @return Activity|null
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param Activity|int|null $activity
     * @return TimesheetQuery
     */
    public function setActivity($activity = null)
    {
        $this->activity = $activity;

        return $this;
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
        if (!is_int($state) && $state !== (int) $state) {
            return $this;
        }

        $state = (int) $state;
        if (in_array($state, [self::STATE_ALL, self::STATE_RUNNING, self::STATE_STOPPED], true)) {
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
        if (!is_int($exported) && $exported !== (int) $exported) {
            return $this;
        }

        $exported = (int) $exported;
        if (in_array($exported, [self::STATE_ALL, self::STATE_EXPORTED, self::STATE_NOT_EXPORTED], true)) {
            $this->exported = $exported;
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBegin()
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

    /**
     * @return \DateTime
     */
    public function getEnd()
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
