<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;

class InvoiceModelCustomerHydrator implements InvoiceModelHydrator
{
    public function hydrate(InvoiceModel $model): array
    {
        $customer = $model->getCustomer();
        $formatter = $model->getFormatter();
        $currency = $model->getCurrency();

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
            'customer.fixed_rate' => $formatter->getFormattedMoney($customer->getFixedRate(), $currency),
            'customer.fixed_rate_nc' => $formatter->getFormattedMoney($customer->getFixedRate(), null),
            'customer.fixed_rate_plain' => $customer->getFixedRate(),
            'customer.hourly_rate' => $formatter->getFormattedMoney($customer->getHourlyRate(), $currency),
            'customer.hourly_rate_nc' => $formatter->getFormattedMoney($customer->getHourlyRate(), null),
            'customer.hourly_rate_plain' => $customer->getHourlyRate(),
        ];

        foreach ($customer->getVisibleMetaFields() as $metaField) {
            $values = array_merge($values, [
                'customer.meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}
