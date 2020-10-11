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
    private $configs = [];

    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @param null|string $prefix
     * @return Configuration[]
     */
    public function getConfiguration(?string $prefix = null): array
    {
        return $this->configs;
    }
}
