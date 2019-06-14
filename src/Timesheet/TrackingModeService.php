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

    public function getAvailableIds(): array
    {
        return [
            TimesheetConfiguration::MODE_DEFAULT,
            TimesheetConfiguration::MODE_PUNCH_IN_OUT,
            TimesheetConfiguration::MODE_DURATION_ONLY,
            TimesheetConfiguration::MODE_DURATION_FIXED_START,
        ];
    }

    public function getActiveMode(): TrackingModeInterface
    {
        $mode = $this->configuration->getTrackingMode();

        switch ($mode) {
            case TimesheetConfiguration::MODE_DURATION_ONLY:
                return new DurationOnlyMode($this->dateTime, $this->configuration);

            case TimesheetConfiguration::MODE_PUNCH_IN_OUT:
                return new PunchInOutMode();

            case TimesheetConfiguration::MODE_DURATION_FIXED_START:
                return new DurationFixedStartMode($this->dateTime, $this->configuration);

            case TimesheetConfiguration::MODE_DEFAULT:
                return new DefaultMode($this->dateTime, $this->configuration);
        }

        throw new ServiceNotFoundException(sprintf('Unknown tracking mode "%s"', $mode));
    }
}
