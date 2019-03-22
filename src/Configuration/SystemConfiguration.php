<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

class SystemConfiguration implements SystemBundleConfiguration
{
    use StringAccessibleConfigTrait;

    public function getPrefix(): string
    {
        return 'kimai';
    }

    protected function getConfigurations(ConfigLoaderInterface $repository): array
    {
        return $repository->getConfiguration();
    }
}
