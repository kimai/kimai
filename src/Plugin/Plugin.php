<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

use App\License\PluginLicense;

class Plugin
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $path;
    /**
     * @var PluginLicense
     */
    private $license;
    /**
     * @var bool
     */
    private $isLicensed = true;
    /**
     * @var bool
     */
    private $isExpired = false;
    /**
     * @var PluginMetadata
     */
    private $metadata;

    /**
     * @return PluginMetadata
     */
    public function getMetadata(): PluginMetadata
    {
        return $this->metadata;
    }

    /**
     * @param PluginMetadata $metadata
     * @return Plugin
     */
    public function setMetadata(PluginMetadata $metadata)
    {
        $this->metadata = $metadata;
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

    /**
     * @return PluginLicense|null
     */
    public function getLicense(): ?PluginLicense
    {
        return $this->license;
    }

    /**
     * @param PluginLicense $license
     * @return Plugin
     */
    public function setLicense(PluginLicense $license)
    {
        $this->license = $license;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->isExpired;
    }

    /**
     * @param bool $isExpired
     * @return Plugin
     */
    public function setIsExpired(bool $isExpired)
    {
        $this->isExpired = $isExpired;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLicensed(): bool
    {
        return $this->isLicensed;
    }

    /**
     * @param bool $isLicensed
     * @return Plugin
     */
    public function setIsLicensed(bool $isLicensed)
    {
        $this->isLicensed = $isLicensed;

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
}
