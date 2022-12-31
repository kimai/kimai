<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

final class Plugin
{
    private string $id;
    private string $path;
    private ?PluginMetadata $metadata = null;

    public function __construct(PluginInterface $bundle)
    {
        $this->id = $bundle->getName();
        $this->path = $bundle->getPath();
    }

    public function getMetadata(): ?PluginMetadata
    {
        return $this->metadata;
    }

    public function setMetadata(PluginMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        if ($this->metadata !== null && $this->metadata->getName() !== null) {
            return $this->metadata->getName();
        }

        return $this->id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
