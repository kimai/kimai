<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Form\Model\SystemConfiguration;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Adjust system configurations dynamically.
 */
final class SystemConfigurationEvent extends Event
{
    /**
     * @param SystemConfiguration[] $configurations
     */
    public function __construct(private array $configurations)
    {
    }

    /**
     * @return SystemConfiguration[]
     */
    public function getConfigurations(): array
    {
        return $this->configurations;
    }

    public function addConfiguration(SystemConfiguration $configuration): SystemConfigurationEvent
    {
        $this->configurations[] = $configuration;

        return $this;
    }
}
