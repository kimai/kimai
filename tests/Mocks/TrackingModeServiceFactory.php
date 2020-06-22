<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Configuration\TimesheetConfiguration;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Timesheet\TrackingMode\DefaultMode;
use App\Timesheet\TrackingMode\DurationFixedBeginMode;
use App\Timesheet\TrackingMode\DurationOnlyMode;
use App\Timesheet\TrackingMode\PunchInOutMode;
use App\Timesheet\TrackingModeService;

class TrackingModeServiceFactory extends AbstractMockFactory
{
    public function create(?string $mode = null, ?array $modes = null): TrackingModeService
    {
        if (null === $mode) {
            $mode = 'default';
        }

        $dateTime = (new UserDateTimeFactoryFactory($this->getTestCase()))->create();
        $loader = new TestConfigLoader([]);

        $configuration = new TimesheetConfiguration($loader, ['mode' => $mode]);

        if (null === $modes) {
            $modes = [
                new DefaultMode($dateTime, $configuration, (new RoundingServiceFactory($this->getTestCase()))->create()),
                new PunchInOutMode($dateTime),
                new DurationOnlyMode($dateTime, $configuration),
                new DurationFixedBeginMode($dateTime, $configuration),
            ];
        }

        return new TrackingModeService($configuration, $modes);
    }
}
