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

final class InvoiceModelCustomerHydrator implements InvoiceModelHydrator
{
    use BudgetHydratorTrait;

    public function __construct(private readonly CustomerStatisticService $customerStatisticService)
    {
    }

    public function hydrate(InvoiceModel $model): array
    {
        $customer = $model->getCustomer();

        if (null === $customer) {
            return [];
        }

        $prefix = 'customer.';

        $values = [
            $prefix . 'id' => $customer->getId(),
            $prefix . 'address' => $customer->getAddress() ?? '',
            $prefix . 'name' => $customer->getName() ?? '',
            $prefix . 'contact' => $customer->getContact() ?? '',
            $prefix . 'company' => $customer->getCompany() ?? '',
            $prefix . 'vat' => $customer->getVatId() ?? '', // deprecated since 2.0.15
            $prefix . 'vat_id' => $customer->getVatId() ?? '',
            $prefix . 'number' => $customer->getNumber() ?? '',
            $prefix . 'country' => $customer->getCountry(),
            $prefix . 'homepage' => $customer->getHomepage() ?? '',
            $prefix . 'comment' => $customer->getComment() ?? '',
            $prefix . 'email' => $customer->getEmail() ?? '',
            $prefix . 'fax' => $customer->getFax() ?? '',
            $prefix . 'phone' => $customer->getPhone() ?? '',
            $prefix . 'mobile' => $customer->getMobile() ?? '',
            $prefix . 'invoice_text' => $customer->getInvoiceText() ?? '',
        ];

        $end = $model->getQuery()?->getEnd();
        if ($end !== null) {
            $statistic = $this->customerStatisticService->getBudgetStatisticModel($customer, $end);

            $values = array_merge($values, $this->getBudgetValues($prefix, $statistic, $model));
        }

        foreach ($customer->getMetaFields() as $metaField) {
            $values = array_merge($values, [
                $prefix . 'meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}
