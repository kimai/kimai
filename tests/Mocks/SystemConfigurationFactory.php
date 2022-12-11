<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\SystemConfiguration;
use App\Tests\Configuration\TestConfigLoader;

class SystemConfigurationFactory
{
    /**
     * @param array<mixed> $settings
     * @return SystemConfiguration
     */
    public static function create(ConfigLoaderInterface $repository, array $settings): SystemConfiguration
    {
        return new SystemConfiguration($repository, self::flatten($settings));
    }

    /**
     * @param array<mixed> $settings
     * @return SystemConfiguration
     */
    public static function createStub(array $settings = []): SystemConfiguration
    {
        return new SystemConfiguration(new TestConfigLoader([]), self::flatten($settings));
    }

    /**
     * @param array<mixed> $settings
     * @return array<string, mixed>
     */
    public static function flatten(array $settings): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($settings));
        $newConfig = [];
        foreach ($iterator as $value) {
            $keys = [];
            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }
            $newConfig[implode('.', $keys)] = $value;
        }

        return $newConfig;
    }
}
