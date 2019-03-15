<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\License;

class PluginLicense
{
    public const LICENSE_EXPIRED = 'expired';
    public const LICENSE_CURRENT = 'licensed';

    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $status;
    /**
     * @var \DateTime
     */
    private $validUntil;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PluginLicense
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return PluginLicense
     */
    public function setStatus(string $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getValidUntil(): \DateTime
    {
        return $this->validUntil;
    }

    /**
     * @param \DateTime $validUntil
     * @return PluginLicense
     */
    public function setValidUntil(\DateTime $validUntil)
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'status' => $this->getStatus(),
            'valid_until' => $this->getValidUntil()->format(DATE_ATOM)
        ];
    }
}
