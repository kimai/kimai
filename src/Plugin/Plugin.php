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
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $path;
    /**
     * @var PluginMetadata
     */
    private $metadata;

    /**
     * @return PluginMetadata
     */
    public function getMetadata(): ?PluginMetadata
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
    public function getPath(): ?string
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
     * @return string
     */
    public function getName(): ?string
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
