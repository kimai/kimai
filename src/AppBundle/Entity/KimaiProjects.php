<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KimaiProjects
 *
 * @ORM\Table(name="projects", indexes={@ORM\Index(name="customerID", columns={"customerID"})})
 * @ORM\Entity
 */
class KimaiProjects
{
    /**
     * @var integer
     *
     * @ORM\Column(name="customerID", type="integer", nullable=false)
     */
    private $customerid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", length=65535, nullable=true)
     */
    private $comment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     */
    private $visible = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="filter", type="boolean", nullable=false)
     */
    private $filter = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="trash", type="boolean", nullable=false)
     */
    private $trash = '0';

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
     * @var boolean
     *
     * @ORM\Column(name="internal", type="boolean", nullable=false)
     */
    private $internal = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="projectID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $projectid;



    /**
     * Set customerid
     *
     * @param integer $customerid
     *
     * @return KimaiProjects
     */
    public function setCustomerid($customerid)
    {
        $this->customerid = $customerid;

        return $this;
    }

    /**
     * Get customerid
     *
     * @return integer
     */
    public function getCustomerid()
    {
        return $this->customerid;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return KimaiProjects
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return KimaiProjects
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
     * Set visible
     *
     * @param boolean $visible
     *
     * @return KimaiProjects
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set filter
     *
     * @param boolean $filter
     *
     * @return KimaiProjects
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get filter
     *
     * @return boolean
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set trash
     *
     * @param boolean $trash
     *
     * @return KimaiProjects
     */
    public function setTrash($trash)
    {
        $this->trash = $trash;

        return $this;
    }

    /**
     * Get trash
     *
     * @return boolean
     */
    public function getTrash()
    {
        return $this->trash;
    }

    /**
     * Set budget
     *
     * @param string $budget
     *
     * @return KimaiProjects
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
     * @return KimaiProjects
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
     * @return KimaiProjects
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
     * Set internal
     *
     * @param boolean $internal
     *
     * @return KimaiProjects
     */
    public function setInternal($internal)
    {
        $this->internal = $internal;

        return $this;
    }

    /**
     * Get internal
     *
     * @return boolean
     */
    public function getInternal()
    {
        return $this->internal;
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
}
