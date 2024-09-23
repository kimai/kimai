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
use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Project\ProjectStatisticService;
use App\Repository\Query\InvoiceQuery;

final class InvoiceModelFactory
{
    public function __construct(
        private readonly CustomerStatisticService $customerStatisticService,
        private readonly ProjectStatisticService $projectStatisticService,
        private readonly ActivityStatisticService $activityStatisticService
    ) {
    }

    public function createModel(InvoiceFormatter $formatter, Customer $customer, InvoiceTemplate $template, InvoiceQuery $query): InvoiceModel
    {
        $model = new InvoiceModel($formatter, $this->customerStatisticService, $this->projectStatisticService, $this->activityStatisticService);

        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->setQuery($query);

        return $model;
    }
}
