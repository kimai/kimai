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

        if (null === $customer) {
            return [];
        }

        $formatter = $model->getFormatter();
        $currency = $model->getCurrency();

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
            // budget
            // remaining budget?
            // time-budget
            // remaining time-budget?
        ];

        foreach ($customer->getMetaFields() as $metaField) {
            $values = array_merge($values, [
                'customer.meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}
