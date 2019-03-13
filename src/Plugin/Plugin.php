<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

class Plugin
{
    public const LICENSE_EXPIRED = 'expired';
    public const LICENSE_CURRENT = 'current';

    private $id = '';
    private $name = '';
    private $allowedLicenses = [];
    private $path = '';

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Plugin
     */
    public function setId(string $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Plugin
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getAllowedLicenses(): array
    {
        return $this->allowedLicenses;
    }

    /**
     * @param string[] $allowedLicenses
     * @return Plugin
     */
    public function setAllowedLicenses(array $allowedLicenses)
    {
        $this->allowedLicenses = $allowedLicenses;
        return $this;
    }
    /**
     * @param string $allowedLicense
     * @return Plugin
     */
    public function addAllowedLicense($allowedLicense)
    {
        $this->allowedLicenses[] = $allowedLicense;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return Plugin
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }
}
