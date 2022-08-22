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
    public function __construct(
        private CustomerStatisticService $customerStatisticService,
        private ProjectStatisticService $projectStatisticService,
        private ActivityStatisticService $activityStatisticService
    ) {
    }

    public function createModel(InvoiceFormatter $formatter): InvoiceModel
    {
        return new InvoiceModel($formatter, $this->customerStatisticService, $this->projectStatisticService, $this->activityStatisticService);
    }
}
