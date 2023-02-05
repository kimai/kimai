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
     * @var array<string, string|null>
     */
    private array $configs = [];

    /**
     * @param Configuration[] $configs
     */
    public function __construct(array $configs)
    {
        foreach ($configs as $config) {
            $this->configs[$config->getName()] = $config->getValue();
        }
    }

    /**
     * @return array<string, string|null>
     */
    public function getConfigurations(): array
    {
        return $this->configs;
    }
}
