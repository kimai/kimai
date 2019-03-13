<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * A service to manage plugins.
 */
class PluginManager
{
    /**
     * @var Plugin[]
     */
    private $plugins;
    /**
     * @var
     */
    private $licenseKey;

    /**
     * @param string $licenseKey
     */
    public function __construct(string $licenseKey)
    {
        $this->licenseKey = $licenseKey;
    }

    /**
     * @param Bundle $bundle
     */
    public function addPlugin(Bundle $bundle)
    {
        $plugin = $this->getPlugin($bundle);
        $this->plugins[$plugin->getId()] = $plugin;
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @param Bundle $bundle
     * @return Plugin
     */
    protected function getPlugin(Bundle $bundle)
    {
        $plugin = new Plugin();
        $plugin
            ->setId($bundle->getName())
            ->setName($bundle->getName())
            ->setPath($bundle->getPath())
        ;

        if ($bundle instanceof PluginInterface) {
            // TODO
            $plugin->setAllowedLicenses([]);
        } else {
            // TODO
            $this->detectLicenseRequirementsViaComposer($plugin);
        }

        return $plugin;
    }

    /**
     * @param Plugin $plugin
     */
    protected function detectLicenseRequirementsViaComposer(Plugin $plugin)
    {
        // TODO
        $plugin->setAllowedLicenses([]);
    }
}
