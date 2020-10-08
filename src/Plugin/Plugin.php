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
    private $id;
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

    public function getMetadata(): ?PluginMetadata
    {
        return $this->metadata;
    }

    public function setMetadata(PluginMetadata $metadata): Plugin
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): Plugin
    {
        $this->path = $path;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): Plugin
    {
        $this->name = $name;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): Plugin
    {
        $this->id = $id;

        return $this;
    }
}
