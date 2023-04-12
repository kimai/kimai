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

    public function __construct(private CustomerStatisticService $customerStatisticService)
    {
    }

    public function hydrate(InvoiceModel $model): array
    {
        $customer = $model->getCustomer();

        if (null === $customer) {
            return [];
        }

        $values = [
            'customer.id' => $customer->getId(),
            'customer.address' => $customer->getAddress() ?? '',
            'customer.name' => $customer->getName() ?? '',
            'customer.contact' => $customer->getContact() ?? '',
            'customer.company' => $customer->getCompany() ?? '',
            'customer.vat' => $customer->getVatId() ?? '', // deprecated since 2.0.15
            'customer.vat_id' => $customer->getVatId() ?? '',
            'customer.number' => $customer->getNumber() ?? '',
            'customer.country' => $customer->getCountry(),
            'customer.homepage' => $customer->getHomepage() ?? '',
            'customer.comment' => $customer->getComment() ?? '',
            'customer.email' => $customer->getEmail() ?? '',
            'customer.fax' => $customer->getFax() ?? '',
            'customer.phone' => $customer->getPhone() ?? '',
            'customer.mobile' => $customer->getMobile() ?? '',
            'customer.invoice_text' => $customer->getInvoiceText() ?? '',
        ];

        $statistic = $this->customerStatisticService->getBudgetStatisticModel($customer, $model->getQuery()->getEnd());

        $values = array_merge($values, $this->getBudgetValues('customer.', $statistic, $model));

        foreach ($customer->getMetaFields() as $metaField) {
            $values = array_merge($values, [
                'customer.meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}
