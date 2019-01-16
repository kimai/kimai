<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Activity;
use App\Entity\User;

/**
 * Can be used for advanced timesheet repository queries.
 */
class TimesheetQuery extends ActivityQuery
{
    public const STATE_ALL = 1;
    public const STATE_RUNNING = 2;
    public const STATE_STOPPED = 3;

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
     * @var User
     */
    protected $user;
    /**
     * @var Activity
     */
    protected $activity;
    /**
     * @var int
     */
    protected $state = self::STATE_ALL;
    /**
     * @var \DateTime
     */
    protected $begin;
    /**
     * @var \DateTime
     */
    protected $end;
    /**
     * @var string
     */
    protected $tags;
    /**
     * @var array
     */
    protected $tagIdList;

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return TimesheetQuery
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Activity overwrites: setProject() and setCustomer()
     *
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param Activity $activity
     * @return TimesheetQuery
     */
    public function setActivity(Activity $activity = null)
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
        if (!is_int($state) && $state != (int)$state) {
            return $this;
        }

        $state = (int)$state;
        if (in_array($state, [self::STATE_ALL, self::STATE_RUNNING, self::STATE_STOPPED], true)) {
            $this->state = $state;
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * @param \DateTime $begin
     * @return TimesheetQuery
     */
    public function setBegin($begin)
    {
        $this->begin = $begin;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param \DateTime $end
     * @return TimesheetQuery
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param $string
     * @return $this
     */
    public function setTags($string)
    {
        $this->tags = $string;

        return $this;
    }

    /**
     * Wheather variable was setted
     * @return bool
     */
    public function hasTags()
    {
        return NULL !== $this->tags && '' !== $this->tags;
    }

    /**
     * Get array with tag-ids
     * @return array
     */
    public function getTagIdArray()
    {
        return $this->tagIdList;
    }

    /**
     * Set tagIds in query as list
     * @param array $tagIdList
     */
    public function setTagIdArray($tagIdList = array())
    {
        $this->tagIdList = $tagIdList;
    }
}
