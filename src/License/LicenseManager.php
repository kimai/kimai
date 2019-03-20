<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\License;

class LicenseManager implements LicenseManagerInterface
{
    /**
     * @var LicenseKeyInterface
     */
    protected $licenseKey;
    /**
     * @var string
     */
    protected $projectDirectory;

    /**
     * @param LicenseKeyInterface $licenseKey
     * @param string $projectDirectory
     */
    public function __construct(LicenseKeyInterface $licenseKey, string $projectDirectory)
    {
        $this->licenseKey = $licenseKey;
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @return PluginLicense[]
     * @throws LicenseException
     */
    public function getPluginLicenses(): array
    {
        $plugins = $this->getLicenseData();
        $licenses = [];
        $expectedKeys = ['name', 'status', 'valid_until'];

        foreach ($plugins as $id => $plugin) {
            foreach ($expectedKeys as $key) {
                if (!isset($plugin[$key])) {
                    throw new LicenseException('Invalid plugin license data, missing: ' . $key);
                }
            }

            $license = new PluginLicense();
            $license->setName($plugin['name']);
            $license->setStatus($plugin['status']);
            $license->setValidUntil(\DateTime::createFromFormat(DATE_ATOM, $plugin['valid_until']));

            $licenses[] = $license;
        }

        return $licenses;
    }

    /**
     * @return array
     * @throws LicenseException
     */
    protected function getLicenseData(): array
    {
        $licenseFile = realpath($this->projectDirectory . '/var/data') . '/LICENSE.txt';

        if (!file_exists($licenseFile)) {
            return [];
        }

        if (!is_readable($licenseFile)) {
            throw new LicenseException('License file is not readable');
        }

        $data = file_get_contents($licenseFile);
        $data = $this->licenseKey->decrypt($data);

        $data = json_decode($data, true);

        if (!isset($data['checksum']) || !isset($data['plugins'])) {
            throw new LicenseException('Incomplete license data');
        }

        if (md5(json_encode($data['plugins'])) !== $data['checksum']) {
            throw new LicenseException('Invalid license checksum');
        }

        return $data['plugins'];
    }
}
