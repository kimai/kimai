<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Configuration\TimesheetConfiguration;
use App\Timesheet\TrackingMode\DefaultMode;
use App\Timesheet\TrackingMode\DurationFixedStartMode;
use App\Timesheet\TrackingMode\DurationOnlyMode;
use App\Timesheet\TrackingMode\PunchInOutMode;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class TrackingModeService
{
    /**
     * @var UserDateTimeFactory
     */
    protected $dateTime;
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;

    public function __construct(UserDateTimeFactory $dateTime, TimesheetConfiguration $configuration)
    {
        $this->dateTime = $dateTime;
        $this->configuration = $configuration;
    }

    /**
     * @return TrackingModeInterface[]
     */
    public function getModes(): iterable
    {
        return [
            new DefaultMode($this->dateTime, $this->configuration),
            new PunchInOutMode(),
            new DurationOnlyMode($this->dateTime, $this->configuration),
            new DurationFixedStartMode($this->dateTime, $this->configuration),
        ];
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
