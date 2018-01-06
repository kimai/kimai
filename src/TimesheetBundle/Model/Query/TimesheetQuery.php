<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Model\Query;

use AppBundle\Entity\User;
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Entity\Project;

/**
 * Can be used for advanced timesheet repository queries.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetQuery
{

    const DEFAULT_PAGESIZE = 25;
    const DEFAULT_PAGE = 1;

    const STATE_ALL = 0;
    const STATE_RUNNING = 1;
    const STATE_STOPPED = 2;

    /**
     * @var User
     */
    protected $user;
    /**
     * @var Activity
     */
    protected $activity;
    /**
     * @var Project
     */
    protected $project;
    /**
     * @var Customer
     */
    protected $customer;
    /**
     * @var int
     */
    protected $page = self::DEFAULT_PAGE;
    /**
     * @var int
     */
    protected $pageSize = self::DEFAULT_PAGESIZE;
    /**
     * @var int
     */
    protected $state = self::STATE_ALL;

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return Timesheet
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
     * @return Timesheet
     */
    public function setActivity(Activity $activity = null)
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Project overwrites: setCustomer()
     * Is overwritten by: setActivity()
     *
     * @param Project $project
     * @return Timesheet
     */
    public function setProject(Project $project = null)
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Project overwrites: none
     * Is overwritten by: setActivity() and setProject()
     *
     * @param Customer $customer
     * @return Timesheet
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return Timesheet
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return Timesheet
     */
    public function setPageSize($pageSize)
    {
        if (!empty($pageSize) && (int) $pageSize > 0) {
            $this->pageSize = (int) $pageSize;
        }
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
     * @return Timesheet
     */
    public function setState($state)
    {
        if (in_array($state, [self::STATE_ALL, self::STATE_RUNNING, self::STATE_STOPPED])) {
            $this->state = $state;
        }
        return $this;
    }

}
