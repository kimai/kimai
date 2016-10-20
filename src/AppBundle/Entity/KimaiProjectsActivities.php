<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KimaiProjectsActivities
 *
 * @ORM\Table(name="projects_activities")
 * @ORM\Entity
 */
class KimaiProjectsActivities
{
    /**
     * @var string
     *
     * @ORM\Column(name="budget", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $budget = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="effort", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $effort;

    /**
     * @var string
     *
     * @ORM\Column(name="approved", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $approved;

    /**
     * @var integer
     *
     * @ORM\Column(name="projectID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $projectid;

    /**
     * @var integer
     *
     * @ORM\Column(name="activityID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $activityid;



    /**
     * Set budget
     *
     * @param string $budget
     *
     * @return KimaiProjectsActivities
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
     * Set effort
     *
     * @param string $effort
     *
     * @return KimaiProjectsActivities
     */
    public function setEffort($effort)
    {
        $this->effort = $effort;

        return $this;
    }

    /**
     * Get effort
     *
     * @return string
     */
    public function getEffort()
    {
        return $this->effort;
    }

    /**
     * Set approved
     *
     * @param string $approved
     *
     * @return KimaiProjectsActivities
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
     * Set projectid
     *
     * @param integer $projectid
     *
     * @return KimaiProjectsActivities
     */
    public function setProjectid($projectid)
    {
        $this->projectid = $projectid;

        return $this;
    }

    /**
     * Get projectid
     *
     * @return integer
     */
    public function getProjectid()
    {
        return $this->projectid;
    }

    /**
     * Set activityid
     *
     * @param integer $activityid
     *
     * @return KimaiProjectsActivities
     */
    public function setActivityid($activityid)
    {
        $this->activityid = $activityid;

        return $this;
    }

    /**
     * Get activityid
     *
     * @return integer
     */
    public function getActivityid()
    {
        return $this->activityid;
    }
}
