<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Activity\ActivityStatisticService;
use App\Customer\CustomerStatisticService;
use App\Project\ProjectStatisticService;

final class InvoiceModelFactory
{
    private $customerStatisticService;
    private $projectStatisticService;
    private $activityStatisticService;

    public function __construct(CustomerStatisticService $customerStatistic, ProjectStatisticService $projectStatistic, ActivityStatisticService $activityStatistic)
    {
        $this->customerStatisticService = $customerStatistic;
        $this->projectStatisticService = $projectStatistic;
        $this->activityStatisticService = $activityStatistic;
    }

    public function createModel(InvoiceFormatter $formatter): InvoiceModel
    {
        return new InvoiceModel($formatter, $this->customerStatisticService, $this->projectStatisticService, $this->activityStatisticService);
    }
}
