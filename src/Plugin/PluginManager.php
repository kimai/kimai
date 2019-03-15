<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

use App\Constants;
use App\License\PluginLicense;

class PluginManager
{
    /**
     * @var Plugin[]
     */
    private $plugins;
    /**
     * @var PluginLicense[]
     */
    private $licenses = [];

    /**
     * @param PluginLicense $license
     */
    public function addLicense(array $licenseData)
    {
        $license = new PluginLicense();
        $license->setName($licenseData['name']);
        $license->setStatus($licenseData['status']);
        $license->setValidUntil(\DateTime::createFromFormat(DATE_ATOM, $licenseData['valid_until']));

        $this->licenses[] = $license;
    }

    /**
     * @param PluginInterface $plugin
     * @throws \Exception
     */
    public function addPlugin(PluginInterface $plugin)
    {
        if (isset($this->plugins[$plugin->getName()])) {
            return;
        }

        $this->plugins[$plugin->getName()] = $this->getPlugin($plugin);
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @param PluginInterface $bundle
     * @return Plugin
     */
    protected function getPlugin(PluginInterface $bundle)
    {
        $plugin = new Plugin();
        $plugin
            ->setName($bundle->getName())
            ->setPath($bundle->getPath())
        ;

        foreach ($this->licenses as $license) {
            if ($license->getName() === $plugin->getName()) {
                $plugin->setLicense($license);
            }
        }

        if (empty($bundle->getLicenseRequirements())) {
            return $plugin;
        }

        $plugin->setIsLicensed(false);
        $plugin->setIsExpired(true);
        $license = $plugin->getLicense();

        if (null === $license) {
            return $plugin;
        }

        if (in_array($license->getStatus(), $bundle->getLicenseRequirements())) {
            $plugin->setIsLicensed(true);
        }

        if ($license->getValidUntil()->getTimestamp() > (new \DateTime())->getTimestamp()) {
            $plugin->setIsExpired(false);
        } else {
            if (!in_array(PluginLicense::LICENSE_EXPIRED, $bundle->getLicenseRequirements())) {
                $plugin->setIsLicensed(false);
            }
        }

        return $plugin;
    }

    /**
     * Call this method and pass a plugin, to set its metdata.
     * This is not pre-filled by default, as it would mean to parse several composer.json on each request.
     *
     * @param Plugin $plugin
     * @return null
     */
    public function loadMetadata(Plugin $plugin)
    {
        $composer = $plugin->getPath() . '/composer.json';
        if (!file_exists($composer) || !is_readable($composer)) {
            return null;
        }

        $json = json_decode(file_get_contents($composer), true);

        $homepage = isset($json['homepage']) ? $json['homepage'] : Constants::HOMEPAGE . '/store/';
        $metadata = new PluginMetadata();
        $metadata
            ->setHomepage($homepage)
            ->setKimaiVersion($json['extra']['kimai']['require'])
            ->setVersion($json['extra']['kimai']['version'])
        ;

        $plugin->setMetadata($metadata);
    }
}
