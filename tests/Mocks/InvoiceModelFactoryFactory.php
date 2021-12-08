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
use App\Invoice\InvoiceModelFactory;
use App\Project\ProjectStatisticService;

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

        return new InvoiceModelFactory($customerStatistic, $projectStatistic, $activityStatistic);
    }
}
