<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KimaiExpenses
 *
 * @ORM\Table(name="expenses", indexes={@ORM\Index(name="userID", columns={"userID"}), @ORM\Index(name="projectID", columns={"projectID"})})
 * @ORM\Entity
 */
class KimaiExpenses
{
    /**
     * @var integer
     *
     * @ORM\Column(name="timestamp", type="integer", nullable=false)
     */
    private $timestamp = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="userID", type="integer", nullable=false)
     */
    private $userid;

    /**
     * @var integer
     *
     * @ORM\Column(name="projectID", type="integer", nullable=false)
     */
    private $projectid;

    /**
     * @var string
     *
     * @ORM\Column(name="designation", type="text", length=65535, nullable=false)
     */
    private $designation;

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
     * @ORM\Column(name="refundable", type="boolean", nullable=false)
     */
    private $refundable = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="cleared", type="boolean", nullable=false)
     */
    private $cleared = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="multiplier", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $multiplier = '1.00';

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $value = '0.00';

    /**
     * @var integer
     *
     * @ORM\Column(name="expenseID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $expenseid;



    /**
     * Set timestamp
     *
     * @param integer $timestamp
     *
     * @return KimaiExpenses
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return integer
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set userid
     *
     * @param integer $userid
     *
     * @return KimaiExpenses
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid
     *
     * @return integer
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set projectid
     *
     * @param integer $projectid
     *
     * @return KimaiExpenses
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
     * Set designation
     *
     * @param string $designation
     *
     * @return KimaiExpenses
     */
    public function setDesignation($designation)
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * Get designation
     *
     * @return string
     */
    public function getDesignation()
    {
        return $this->designation;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return KimaiExpenses
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
     * @return KimaiExpenses
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
     * Set refundable
     *
     * @param boolean $refundable
     *
     * @return KimaiExpenses
     */
    public function setRefundable($refundable)
    {
        $this->refundable = $refundable;

        return $this;
    }

    /**
     * Get refundable
     *
     * @return boolean
     */
    public function getRefundable()
    {
        return $this->refundable;
    }

    /**
     * Set cleared
     *
     * @param boolean $cleared
     *
     * @return KimaiExpenses
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
     * Set multiplier
     *
     * @param string $multiplier
     *
     * @return KimaiExpenses
     */
    public function setMultiplier($multiplier)
    {
        $this->multiplier = $multiplier;

        return $this;
    }

    /**
     * Get multiplier
     *
     * @return string
     */
    public function getMultiplier()
    {
        return $this->multiplier;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return KimaiExpenses
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get expenseid
     *
     * @return integer
     */
    public function getExpenseid()
    {
        return $this->expenseid;
    }
}
