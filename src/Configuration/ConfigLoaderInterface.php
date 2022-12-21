<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Entity\Configuration;

interface ConfigLoaderInterface
{
    /**
     * @param string $name
     * @return ?Configuration
     */
    public function getConfiguration(string $name): ?Configuration;

    /**
     * @return Configuration[]
     */
    public function getConfigurations(): array;
}
