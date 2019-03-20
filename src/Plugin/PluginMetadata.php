<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

class PluginMetadata
{
    /**
     * @var string
     */
    private $version;
    /**
     * @var string
     */
    private $kimaiVersion;
    /**
     * @var string
     */
    private $homepage;

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return PluginMetadata
     */
    public function setVersion(string $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getKimaiVersion(): string
    {
        return $this->kimaiVersion;
    }

    /**
     * @param string $kimaiVersion
     * @return PluginMetadata
     */
    public function setKimaiVersion(string $kimaiVersion)
    {
        $this->kimaiVersion = $kimaiVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getHomepage(): string
    {
        return $this->homepage;
    }

    /**
     * @param string $homepage
     * @return PluginMetadata
     */
    public function setHomepage(string $homepage)
    {
        $this->homepage = $homepage;

        return $this;
    }
}
