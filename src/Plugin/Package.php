<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

/**
 * @internaö
 */
final readonly class Package
{
    public function __construct(private string $packagePath, private PluginMetadata $pluginMetadata)
    {
    }

    public function getPackagePath(): string
    {
        return $this->packagePath;
    }

    public function getMetadata(): PluginMetadata
    {
        return $this->pluginMetadata;
    }
}
