<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Activity\ActivityStatisticService;
use App\Customer\CustomerStatisticService;
use App\Invoice\Hydrator\InvoiceItemDefaultHydrator;
use App\Invoice\Hydrator\InvoiceModelActivityHydrator;
use App\Invoice\Hydrator\InvoiceModelCustomerHydrator;
use App\Invoice\Hydrator\InvoiceModelDefaultHydrator;
use App\Invoice\Hydrator\InvoiceModelIssuerHydrator;
use App\Invoice\Hydrator\InvoiceModelProjectHydrator;
use App\Invoice\Hydrator\InvoiceModelUserHydrator;
use App\Invoice\InvoiceModelFactory;
use App\Project\ProjectStatisticService;
use App\Timesheet\RateCalculator\DecimalRateCalculator;

class InvoiceModelFactoryFactory extends AbstractMockFactory
{
    public function create(): InvoiceModelFactory
    {
        /** @var CustomerStatisticService $customerStatistic */
        $customerStatistic = $this->getMockBuilder(CustomerStatisticService::class)->disableOriginalConstructor()->getMock();
        /** @var ProjectStatisticService $projectStatistic */
        $projectStatistic = $this->getMockBuilder(ProjectStatisticService::class)->disableOriginalConstructor()->getMock();
        /** @var ActivityStatisticService $activityStatistic */
        $activityStatistic = $this->getMockBuilder(ActivityStatisticService::class)->disableOriginalConstructor()->getMock();

        $modelHydrators = [
            new InvoiceModelDefaultHydrator(),
            new InvoiceModelCustomerHydrator($customerStatistic),
            new InvoiceModelIssuerHydrator(),
            new InvoiceModelProjectHydrator($projectStatistic),
            new InvoiceModelActivityHydrator($activityStatistic),
            new InvoiceModelUserHydrator(),
        ];
        $itemHydrators = [
            new InvoiceItemDefaultHydrator(),
        ];

        return new InvoiceModelFactory(new DecimalRateCalculator(), $modelHydrators, $itemHydrators);
    }
}
