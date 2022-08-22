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
 * This event should be used, if system configurations should be changed/added dynamically.
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

    /**
     * @param SystemConfiguration $configuration
     * @return SystemConfigurationEvent
     */
    public function addConfiguration(SystemConfiguration $configuration): SystemConfigurationEvent
    {
        $this->configurations[] = $configuration;

        return $this;
    }
}
