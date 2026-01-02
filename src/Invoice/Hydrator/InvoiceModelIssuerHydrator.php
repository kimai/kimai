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
use Symfony\Component\Intl\Countries;

final class InvoiceModelIssuerHydrator implements InvoiceModelHydrator
{
    /**
     * @return array<string, mixed>
     */
    public function hydrate(InvoiceModel $model): array
    {
        $customer = $model->getTemplate()->getCustomer();
        if (null === $customer) {
            return [];
        }

        $prefix = 'issuer.';
        $language = $model->getTemplate()->getLanguage();
        $country = $customer->getCountry();

        $values = [
            $prefix . 'id' => $customer->getId(),
            $prefix . 'address' => $customer->getFormattedAddress() ?? '',
            $prefix . 'address_line1' => $customer->getAddressLine1() ?? '',
            $prefix . 'address_line2' => $customer->getAddressLine2() ?? '',
            $prefix . 'address_line3' => $customer->getAddressLine3() ?? '',
            $prefix . 'postcode' => $customer->getPostCode() ?? '',
            $prefix . 'city' => $customer->getCity() ?? '',
            $prefix . 'name' => $customer->getName() ?? '',
            $prefix . 'contact' => $customer->getContact() ?? '',
            $prefix . 'company' => $customer->getCompany() ?? '',
            $prefix . 'vat_id' => $customer->getVatId() ?? '',
            $prefix . 'number' => $customer->getNumber() ?? '',
            $prefix . 'country' => $country,
            $prefix . 'country_name' => $country !== null ? Countries::getName($country, $language) : null,
            $prefix . 'homepage' => $customer->getHomepage() ?? '',
            $prefix . 'comment' => $customer->getComment() ?? '',
            $prefix . 'email' => $customer->getEmail() ?? '',
            $prefix . 'fax' => $customer->getFax() ?? '',
            $prefix . 'phone' => $customer->getPhone() ?? '',
            $prefix . 'mobile' => $customer->getMobile() ?? '',
            $prefix . 'invoice_text' => $customer->getInvoiceText() ?? '',
            $prefix . 'buyer_reference' => $customer->getBuyerReference() ?? '',
        ];

        foreach ($customer->getMetaFields() as $metaField) {
            $values = array_merge($values, [
                $prefix . 'meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}
