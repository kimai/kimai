<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KimaiUsers
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"}), @ORM\UniqueConstraint(name="apikey", columns={"apikey"})})
 * @ORM\Entity
 */
class KimaiUsers
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=160, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=160, nullable=true)
     */
    private $alias;

    /**
     * @var boolean
     *
     * @ORM\Column(name="trash", type="boolean", nullable=false)
     */
    private $trash = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="mail", type="string", length=160, nullable=false)
     */
    private $mail = '';

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=254, nullable=true)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="passwordResetHash", type="string", length=32, nullable=true)
     */
    private $passwordresethash;

    /**
     * @var integer
     *
     * @ORM\Column(name="ban", type="integer", nullable=false)
     */
    private $ban = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="banTime", type="integer", nullable=false)
     */
    private $bantime = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="secure", type="string", length=60, nullable=false)
     */
    private $secure = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="lastProject", type="integer", nullable=false)
     */
    private $lastproject = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="lastActivity", type="integer", nullable=false)
     */
    private $lastactivity = '1';

    /**
     * @var integer
     *
     * @ORM\Column(name="lastRecord", type="integer", nullable=false)
     */
    private $lastrecord = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="timeframeBegin", type="string", length=60, nullable=false)
     */
    private $timeframebegin = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="timeframeEnd", type="string", length=60, nullable=false)
     */
    private $timeframeend = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="apikey", type="string", length=30, nullable=true)
     */
    private $apikey;

    /**
     * @var integer
     *
     * @ORM\Column(name="globalRoleID", type="integer", nullable=false)
     */
    private $globalroleid;

    /**
     * @var integer
     *
     * @ORM\Column(name="userID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userid;



    /**
     * Set name
     *
     * @param string $name
     *
     * @return KimaiUsers
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
     * Set alias
     *
     * @param string $alias
     *
     * @return KimaiUsers
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set trash
     *
     * @param boolean $trash
     *
     * @return KimaiUsers
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
     * Set active
     *
     * @param boolean $active
     *
     * @return KimaiUsers
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set mail
     *
     * @param string $mail
     *
     * @return KimaiUsers
     */
    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * Get mail
     *
     * @return string
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return KimaiUsers
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set passwordresethash
     *
     * @param string $passwordresethash
     *
     * @return KimaiUsers
     */
    public function setPasswordresethash($passwordresethash)
    {
        $this->passwordresethash = $passwordresethash;

        return $this;
    }

    /**
     * Get passwordresethash
     *
     * @return string
     */
    public function getPasswordresethash()
    {
        return $this->passwordresethash;
    }

    /**
     * Set ban
     *
     * @param integer $ban
     *
     * @return KimaiUsers
     */
    public function setBan($ban)
    {
        $this->ban = $ban;

        return $this;
    }

    /**
     * Get ban
     *
     * @return integer
     */
    public function getBan()
    {
        return $this->ban;
    }

    /**
     * Set bantime
     *
     * @param integer $bantime
     *
     * @return KimaiUsers
     */
    public function setBantime($bantime)
    {
        $this->bantime = $bantime;

        return $this;
    }

    /**
     * Get bantime
     *
     * @return integer
     */
    public function getBantime()
    {
        return $this->bantime;
    }

    /**
     * Set secure
     *
     * @param string $secure
     *
     * @return KimaiUsers
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;

        return $this;
    }

    /**
     * Get secure
     *
     * @return string
     */
    public function getSecure()
    {
        return $this->secure;
    }

    /**
     * Set lastproject
     *
     * @param integer $lastproject
     *
     * @return KimaiUsers
     */
    public function setLastproject($lastproject)
    {
        $this->lastproject = $lastproject;

        return $this;
    }

    /**
     * Get lastproject
     *
     * @return integer
     */
    public function getLastproject()
    {
        return $this->lastproject;
    }

    /**
     * Set lastactivity
     *
     * @param integer $lastactivity
     *
     * @return KimaiUsers
     */
    public function setLastactivity($lastactivity)
    {
        $this->lastactivity = $lastactivity;

        return $this;
    }

    /**
     * Get lastactivity
     *
     * @return integer
     */
    public function getLastactivity()
    {
        return $this->lastactivity;
    }

    /**
     * Set lastrecord
     *
     * @param integer $lastrecord
     *
     * @return KimaiUsers
     */
    public function setLastrecord($lastrecord)
    {
        $this->lastrecord = $lastrecord;

        return $this;
    }

    /**
     * Get lastrecord
     *
     * @return integer
     */
    public function getLastrecord()
    {
        return $this->lastrecord;
    }

    /**
     * Set timeframebegin
     *
     * @param string $timeframebegin
     *
     * @return KimaiUsers
     */
    public function setTimeframebegin($timeframebegin)
    {
        $this->timeframebegin = $timeframebegin;

        return $this;
    }

    /**
     * Get timeframebegin
     *
     * @return string
     */
    public function getTimeframebegin()
    {
        return $this->timeframebegin;
    }

    /**
     * Set timeframeend
     *
     * @param string $timeframeend
     *
     * @return KimaiUsers
     */
    public function setTimeframeend($timeframeend)
    {
        $this->timeframeend = $timeframeend;

        return $this;
    }

    /**
     * Get timeframeend
     *
     * @return string
     */
    public function getTimeframeend()
    {
        return $this->timeframeend;
    }

    /**
     * Set apikey
     *
     * @param string $apikey
     *
     * @return KimaiUsers
     */
    public function setApikey($apikey)
    {
        $this->apikey = $apikey;

        return $this;
    }

    /**
     * Get apikey
     *
     * @return string
     */
    public function getApikey()
    {
        return $this->apikey;
    }

    /**
     * Set globalroleid
     *
     * @param integer $globalroleid
     *
     * @return KimaiUsers
     */
    public function setGlobalroleid($globalroleid)
    {
        $this->globalroleid = $globalroleid;

        return $this;
    }

    /**
     * Get globalroleid
     *
     * @return integer
     */
    public function getGlobalroleid()
    {
        return $this->globalroleid;
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
}
