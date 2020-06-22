<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Configuration\TimesheetConfiguration;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class TrackingModeService
{
    /**
     * @var TrackingModeInterface[]
     */
    private $modes = [];
    /**
     * @var TimesheetConfiguration
     */
    private $configuration;

    /**
     * @param TimesheetConfiguration $configuration
     * @param TrackingModeInterface[] $modes
     */
    public function __construct(TimesheetConfiguration $configuration, iterable $modes)
    {
        $this->configuration = $configuration;
        $this->modes = $modes;
    }

    /**
     * @return TrackingModeInterface[]
     */
    public function getModes(): iterable
    {
        return $this->modes;
    }

    public function getActiveMode(): TrackingModeInterface
    {
        $trackingMode = $this->configuration->getTrackingMode();

        foreach ($this->getModes() as $mode) {
            if ($mode->getId() === $trackingMode) {
                return $mode;
            }
        }

        throw new ServiceNotFoundException($trackingMode);
    }
}
