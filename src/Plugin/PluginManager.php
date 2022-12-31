<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

final class PluginManager
{
    /**
     * @var array<Plugin>|null
     */
    private ?array $plugins = null;
    /**
     * @var iterable<PluginInterface>
     */
    private iterable $bundles;

    /**
     * @param iterable<PluginInterface> $plugins
     */
    public function __construct(iterable $plugins)
    {
        $this->bundles = $plugins;
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins(): array
    {
        if ($this->plugins === null) {
            $plugins = [];

            foreach ($this->bundles as $bundle) {
                $plugins[$bundle->getName()] = new Plugin($bundle);
            }

            $this->plugins = array_values($plugins);
        }

        return $this->plugins;
    }

    public function getPlugin(string $name): ?Plugin
    {
        $plugins = $this->getPlugins();

        foreach ($plugins as $plugin) {
            if ($plugin->getName() === $name) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Call this method and pass a plugin, to set its metadata.
     * This is not pre-filled by default, as it would mean to parse several composer.json on each request.
     *
     * @param Plugin $plugin
     * @throws \Exception
     */
    public function loadMetadata(Plugin $plugin): void
    {
        $meta = PluginMetadata::loadFromComposer($plugin->getPath());
        $plugin->setMetadata($meta);
    }
}
