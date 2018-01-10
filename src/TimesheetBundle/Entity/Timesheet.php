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
 * @ORM\Entity(repositoryClass="TimesheetBundle\Repository\TimesheetRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class Timesheet
{

    /**
     * @var integer
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
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    private $duration = 0;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id")
     * @Assert\NotNull()
     */
    private $user;

    /**
     * @var Activity
     *
     * @ORM\ManyToOne(targetEntity="TimesheetBundle\Entity\Activity", inversedBy="timesheets")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Assert\NotNull()
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
     * @ORM\Column(name="rate", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $rate = 0.00;

    /**
     * Get entry id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return Timesheet
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
     * @return Timesheet
     */
    public function setEnd($end)
    {
        $this->end = $end;
        if ($end === null) {
            $this->duration = 0;
        }

        return $this;
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
        if ($this->begin === null) {
            return 0;
        }

        if ($this->end === null) {
            $current = new \DateTime();
            return $current->getTimestamp() - $this->begin->getTimestamp();
        }

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
     * Set activity
     *
     * @param Activity $activity
     *
     * @return Timesheet
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * Get Activity
     *
     * @return Activity
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
}
