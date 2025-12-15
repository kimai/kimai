<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\RateCalculator;

use App\Configuration\SystemConfiguration;

/**
 * @final
 */
class RateCalculatorFactory
{
    public function __construct(private readonly SystemConfiguration $configuration)
    {
    }

    /**
     * @internal do not call, use RateCalculatorMode injection
     */
    public function getRateCalculatorMode(): RateCalculatorMode
    {
        if ($this->configuration->find('invoice.rounding_mode') === 'decimal') {
            return new DecimalRateCalculator();
        }

        return new ClassicRateCalculator();
    }
}
