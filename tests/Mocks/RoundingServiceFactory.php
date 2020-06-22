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
use App\Timesheet\Rounding\CeilRounding;
use App\Timesheet\Rounding\ClosestRounding;
use App\Timesheet\Rounding\DefaultRounding;
use App\Timesheet\Rounding\FloorRounding;
use App\Timesheet\RoundingService;

class RoundingServiceFactory extends AbstractMockFactory
{
    public function create(?array $rules = null): RoundingService
    {
        $loader = new TestConfigLoader([]);

        if (null === $rules) {
            $rules = [
                'default' => [
                    'days' => 'monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                    'begin' => 0,
                    'end' => 0,
                    'duration' => 0,
                    'mode' => 'default'
                ]
            ];
        }

        $configuration = new TimesheetConfiguration($loader, [
            'rounding' => $rules
        ]);

        $modes = [
            new CeilRounding(),
            new ClosestRounding(),
            new DefaultRounding(),
            new FloorRounding(),
        ];

        return new RoundingService($configuration, $modes, $rules);
    }
}
