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
final class Package
{
    public function __construct(private readonly \SplFileInfo $packageFile, private readonly PluginMetadata $pluginMetadata)
    {
    }

    public function getPackageFile(): \SplFileInfo
    {
        return $this->packageFile;
    }

    public function getMetadata(): PluginMetadata
    {
        return $this->pluginMetadata;
    }
}
