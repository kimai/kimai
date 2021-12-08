<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Customer\CustomerStatisticService;
use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;

class InvoiceModelCustomerHydrator implements InvoiceModelHydrator
{
    private $customerStatistic;

    public function __construct(CustomerStatisticService $customerStatistic)
    {
        $this->customerStatistic = $customerStatistic;
    }

    public function hydrate(InvoiceModel $model): array
    {
        $customer = $model->getCustomer();

        if (null === $customer) {
            return [];
        }

        $values = [
            'customer.id' => $customer->getId(),
            'customer.address' => $customer->getAddress(),
            'customer.name' => $customer->getName(),
            'customer.contact' => $customer->getContact(),
            'customer.company' => $customer->getCompany(),
            'customer.vat' => $customer->getVatId(),
            'customer.number' => $customer->getNumber(),
            'customer.country' => $customer->getCountry(),
            'customer.homepage' => $customer->getHomepage(),
            'customer.comment' => $customer->getComment(),
            'customer.email' => $customer->getEmail(),
            'customer.fax' => $customer->getFax(),
            'customer.phone' => $customer->getPhone(),
            'customer.mobile' => $customer->getMobile(),
        ];

        $statistic = $this->customerStatistic->getBudgetStatisticModel($customer, $model->getQuery()->getEnd());
        $currency = $model->getCurrency();
        $formatter = $model->getFormatter();

        if ($model->getTemplate()->isDecimalDuration()) {
            $budgetOpenDuration = $formatter->getFormattedDecimalDuration($statistic->getTimeBudgetOpen());
        } else {
            $budgetOpenDuration = $formatter->getFormattedDuration($statistic->getTimeBudgetOpen());
        }

        $values = array_merge($values, [
            'customer.budget_open' => $formatter->getFormattedMoney($statistic->getBudgetOpen(), $currency),
            'customer.budget_open_plain' => $statistic->getBudgetOpen(),
            'customer.time_budget_open' => $budgetOpenDuration,
            'customer.time_budget_open_plain' => $statistic->getTimeBudgetOpen(),
        ]);

        foreach ($customer->getMetaFields() as $metaField) {
            $values = array_merge($values, [
                'customer.meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}
