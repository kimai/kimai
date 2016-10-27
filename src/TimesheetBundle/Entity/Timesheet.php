<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Entity;

use AppBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Timesheet entity.
 *
 * @ORM\Entity(repositoryClass="TimesheetBundle\Repository\TimesheetRepository")
 * @ORM\Table(name="timeSheet", indexes={@ORM\Index(columns={"userID"}), @ORM\Index(name="activity", columns={"activity"})})
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class Timesheet
{
    /**
     * @var integer
     *
     * @ORM\Column(name="start", type="integer", nullable=false)
     */
    private $start = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="end", type="integer", nullable=false)
     */
    private $end = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", nullable=false)
     */
    private $duration = '0';

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="userID", referencedColumnName="userID")
     */
    private $user;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="TimesheetBundle\Entity\Activity")
     * @ORM\JoinColumn(name="activity", referencedColumnName="id")
     */
    private $activity;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", length=65535, nullable=true)
     */
    private $comment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="commentType", type="boolean", nullable=false)
     */
    private $commenttype = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="cleared", type="boolean", nullable=false)
     */
    private $cleared = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=50, nullable=true)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="trackingNumber", type="string", length=30, nullable=true)
     */
    private $trackingnumber;

    /**
     * @var string
     *
     * @ORM\Column(name="rate", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $rate = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="fixedRate", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $fixedrate = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="budget", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $budget;

    /**
     * @var string
     *
     * @ORM\Column(name="approved", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $approved;

    /**
     * @var integer
     *
     * @ORM\Column(name="statusID", type="smallint", nullable=false)
     */
    private $statusid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="billable", type="boolean", nullable=true)
     */
    private $billable;

    /**
     * @var integer
     *
     * @ORM\Column(name="timeEntryID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $timeentryid;



    /**
     * Set start
     *
     * @param integer $start
     *
     * @return Timesheet
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return integer
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param integer $end
     *
     * @return Timesheet
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return integer
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     *
     * @return Timesheet
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Timesheet
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set activityid
     *
     * @param integer $activity
     *
     * @return Timesheet
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activityid
     *
     * @return integer
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set description
     *
     * @param string $description
     *
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
     * Set comment
     *
     * @param string $comment
     *
     * @return Timesheet
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set commenttype
     *
     * @param boolean $commenttype
     *
     * @return Timesheet
     */
    public function setCommenttype($commenttype)
    {
        $this->commenttype = $commenttype;

        return $this;
    }

    /**
     * Get commenttype
     *
     * @return boolean
     */
    public function getCommenttype()
    {
        return $this->commenttype;
    }

    /**
     * Set cleared
     *
     * @param boolean $cleared
     *
     * @return Timesheet
     */
    public function setCleared($cleared)
    {
        $this->cleared = $cleared;

        return $this;
    }

    /**
     * Get cleared
     *
     * @return boolean
     */
    public function getCleared()
    {
        return $this->cleared;
    }

    /**
     * Set location
     *
     * @param string $location
     *
     * @return Timesheet
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set trackingnumber
     *
     * @param string $trackingnumber
     *
     * @return Timesheet
     */
    public function setTrackingnumber($trackingnumber)
    {
        $this->trackingnumber = $trackingnumber;

        return $this;
    }

    /**
     * Get trackingnumber
     *
     * @return string
     */
    public function getTrackingnumber()
    {
        return $this->trackingnumber;
    }

    /**
     * Set rate
     *
     * @param string $rate
     *
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
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set fixedrate
     *
     * @param string $fixedrate
     *
     * @return Timesheet
     */
    public function setFixedrate($fixedrate)
    {
        $this->fixedrate = $fixedrate;

        return $this;
    }

    /**
     * Get fixedrate
     *
     * @return string
     */
    public function getFixedrate()
    {
        return $this->fixedrate;
    }

    /**
     * Set budget
     *
     * @param string $budget
     *
     * @return Timesheet
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * Get budget
     *
     * @return string
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * Set approved
     *
     * @param string $approved
     *
     * @return Timesheet
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;

        return $this;
    }

    /**
     * Get approved
     *
     * @return string
     */
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * Set statusid
     *
     * @param integer $statusid
     *
     * @return Timesheet
     */
    public function setStatusid($statusid)
    {
        $this->statusid = $statusid;

        return $this;
    }

    /**
     * Get statusid
     *
     * @return integer
     */
    public function getStatusid()
    {
        return $this->statusid;
    }

    /**
     * Set billable
     *
     * @param boolean $billable
     *
     * @return Timesheet
     */
    public function setBillable($billable)
    {
        $this->billable = $billable;

        return $this;
    }

    /**
     * Get billable
     *
     * @return boolean
     */
    public function getBillable()
    {
        return $this->billable;
    }

    /**
     * Get timeentryid
     *
     * @return integer
     */
    public function getTimeentryid()
    {
        return $this->timeentryid;
    }
}
