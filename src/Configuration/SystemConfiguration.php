<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Repository\ConfigurationRepository;

class SystemConfiguration implements SystemBundleConfiguration
{
    use StringAccessibleConfigTrait;

    /**
     * @param array $settings
     */
    public function __construct(ConfigurationRepository $repository, array $settings)
    {
        // this foreach should be replaced by a better piece of code,
        // especially the pointers could be a problem in the future
        foreach($repository->getAllConfigurations() as $key => $value) {
            $temp = explode('.', $key);
            $array = &$settings;
            foreach($temp as $key2) {
                if (!isset($array[$key2])) {
                    continue 2;
                }
                if (is_array($array[$key2])) {
                    $array = &$array[$key2];
                } elseif(is_bool($array[$key2])) {
                    $array[$key2] = (bool) $value;
                } elseif(is_int($array[$key2])) {
                    $array[$key2] = (int) $value;
                } else {
                    $array[$key2] = $value;
                }
            }
        }

        $this->settings = $settings;
    }

    public function getPrefix(): string
    {
        return 'kimai';
    }

    public function createTimesheetConfiguration(): TimesheetConfiguration
    {
        return new TimesheetConfiguration($this->find('timesheet'));
    }

    public function createFormConfiguration(): FormConfiguration
    {
        return new FormConfiguration($this->find('defaults'));
    }
}
