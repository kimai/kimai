<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Form\Model\SystemConfiguration;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event should be used, if system configurations should be changed/added dynamically.
 */
final class SystemConfigurationEvent extends Event
{
    public const CONFIGURE = 'app.system_configuration';

    /**
     * @var SystemConfiguration[]
     */
    protected $preferences;

    /**
     * @param SystemConfiguration[] $configurations
     */
    public function __construct(array $configurations)
    {
        $this->preferences = $configurations;
    }

    /**
     * @return SystemConfiguration[]
     */
    public function getConfigurations()
    {
        return $this->preferences;
    }

    /**
     * @param SystemConfiguration $configuration
     * @return SystemConfigurationEvent
     */
    public function addConfiguration(SystemConfiguration $configuration): SystemConfigurationEvent
    {
        $this->preferences[] = $configuration;

        return $this;
    }
}
