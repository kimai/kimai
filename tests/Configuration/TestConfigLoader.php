<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\ConfigLoaderInterface;
use App\Entity\Configuration;

/**
 * @covers \App\Configuration\SystemConfiguration
 */
class TestConfigLoader implements ConfigLoaderInterface
{
    /**
     * @var Configuration[]
     */
    private array $configs;

    /**
     * @param Configuration[] $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    public function getConfiguration(string $name): ?Configuration
    {
        if (!\array_key_exists($name, $this->configs)) {
            return null;
        }

        return $this->configs[$name];
    }

    /**
     * @return Configuration[]
     */
    public function getConfigurations(): array
    {
        return $this->configs;
    }
}
