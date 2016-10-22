<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserPreference
 *
 * @ORM\Table(name="preferences",indexes={@ORM\Index(name="option_idx", columns={"userid", "option"})}))
 * @ORM\Entity
 */
class UserPreference
{

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="userID", type="integer")
     */
    private $userid;

    /**
     * @var string
     *
     * @ORM\Column(name="option", type="string", length=255)
     */
    private $option;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=false)
     */
    private $value;

    /**
     * Set value
     *
     * @param string $value
     *
     * @return UserPreference
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
     * Set option
     *
     * @param string $option
     *
     * @return UserPreference
     */
    public function setOption($option)
    {
        $this->option = $option;

        return $this;
    }

    /**
     * Get option
     *
     * @return string
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * Set userid
     *
     * @param integer $userid
     *
     * @return UserPreference
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
}
