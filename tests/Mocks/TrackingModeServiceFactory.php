<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Tests\Configuration\TestConfigLoader;
use App\Timesheet\TrackingMode\DefaultMode;
use App\Timesheet\TrackingMode\DurationFixedBeginMode;
use App\Timesheet\TrackingMode\PunchInOutMode;
use App\Timesheet\TrackingModeService;

class TrackingModeServiceFactory extends AbstractMockFactory
{
    public function create(?string $mode = null, ?array $modes = null): TrackingModeService
    {
        if (null === $mode) {
            $mode = 'default';
        }

        $loader = new TestConfigLoader([]);

        $configuration = SystemConfigurationFactory::create($loader, ['timesheet' => ['mode' => $mode]]);

        if (null === $modes) {
            $modes = [
                new DefaultMode((new RoundingServiceFactory($this->getTestCase()))->create()),
                new PunchInOutMode(),
                new DurationFixedBeginMode($configuration),
            ];
        }

        return new TrackingModeService($configuration, $modes);
    }
}
