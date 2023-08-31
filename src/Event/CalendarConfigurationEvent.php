<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class CalendarConfigurationEvent extends Event
{
    /**
     * @param array<string, string|int|bool|array> $configuration
     */
    public function __construct(private array $configuration)
    {
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if (\array_key_exists($key, $this->configuration)) {
                $this->configuration[$key] = $value;
            }
        }
    }
}
