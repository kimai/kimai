<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KimaiStatuses
 *
 * @ORM\Table(name="statuses")
 * @ORM\Entity
 */
class KimaiStatuses
{
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=200, nullable=false)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="statusID", type="boolean")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $statusid;



    /**
     * Set status
     *
     * @param string $status
     *
     * @return KimaiStatuses
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get statusid
     *
     * @return boolean
     */
    public function getStatusid()
    {
        return $this->statusid;
    }
}
