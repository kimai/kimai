<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class PluginManager
{
    /**
     * @var array<Plugin>|null
     */
    private ?array $plugins = null;

    /**
     * @param iterable<PluginInterface> $bundles
     */
    public function __construct(
        #[TaggedIterator(PluginInterface::class)]
        private readonly iterable $bundles
    )
    {
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

    public function hasPlugin(string $name): bool
    {
        foreach ($this->bundles as $plugin) {
            if ($plugin->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    public function getPlugin(string $name): ?Plugin
    {
        $plugins = $this->getPlugins();

        foreach ($plugins as $plugin) {
            if ($plugin->getId() === $name) {
                return $plugin;
            }
        }

        return null;
    }
}
