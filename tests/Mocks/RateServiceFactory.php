<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Repository\TimesheetRepository;
use App\Timesheet\RateCalculator\ClassicRateCalculator;
use App\Timesheet\RateService;

class RateServiceFactory extends AbstractMockFactory
{
    public function create(array $rules = [], array $rates = []): RateService
    {
        $mock = $this->createMock(TimesheetRepository::class);
        if (!empty($rates)) {
            $mock->expects($this->getTestCase()->any())->method('findMatchingRates')->willReturn($rates);
        }

        return new RateService($rules, $mock, new ClassicRateCalculator());
    }
}
