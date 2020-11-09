<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use App\Entity\Customer;

final class GrandtotalCustomerImporter extends AbstractCustomerImporter
{
    protected function findCustomerName(array $row): string
    {
        foreach ($row as $name => $value) {
            switch (strtolower($name)) {
                case 'organization':
                case 'firma':
                    if (!empty($value)) {
                        return $value;
                    }
            }
        }

        throw new UnsupportedFormatException('Missing customer name, expected in one of the columns: "Organization", "Firma"');
    }

    protected function findCustomerNumber(array $row): ?string
    {
        foreach ($row as $name => $value) {
            switch (strtolower($name)) {
                case 'customer number':
                case 'kundennummer':
                    if (!empty($value)) {
                        return $value;
                    }
            }
        }

        return null;
    }

    protected function mapEntryToCustomer(Customer $customer, array $row)
    {
        $names = ['first' => '', 'middle' => '', 'last' => '', 'title' => ''];
        $address = ['street' => '',  'city' => '', 'code' => ''];

        foreach ($row as $name => $value) {
            switch (strtolower($name)) {
                case 'department':
                case 'abteilung':

                case 'salutation':
                case 'briefanrede':

                case 'state':
                case 'bundesland':

                case 'iban':
                case 'bic':

                case 'sepa mandate id':
                case 'sepa mandat':
                    // not supported in Kimai
                    break;

                case 'organization':
                case 'firma':
                    $customer->setCompany($value);
                    break;

                case 'e-mail':
                    if (!empty($value)) {
                        $customer->setEmail($value);
                    }
                    break;

                case 'country':
                case 'land':
                    if (!empty($value)) {
                        $customer->setCountry($value);
                    }
                    break;

                case 'customer number':
                case 'kundennummer':
                    if (!empty($value)) {
                        $customer->setNumber($value);
                    }
                    break;

                case 'tax-id':
                case 'umsatzsteuer':
                    if (!empty($value)) {
                        $customer->setVatId($value);
                    }
                    break;

                case 'note':
                case 'notiz':
                    if (!empty($value)) {
                        $customer->setComment(strip_tags($value));
                    }
                break;

                case 'title':
                case 'titel':
                    $names['title'] = $value;
                    break;

                case 'first name':
                case 'vorname':
                    $names['first'] = $value;
                    break;

                case 'middle name':
                case 'zweiter vorname':
                    $names['middle'] = $value;
                    break;

                case 'last name':
                case 'nachname':
                    $names['last'] = $value;
                    break;

                case 'street':
                case 'straße':
                    $address['street'] = $value;
                    break;

                case 'zip':
                case 'plz':
                    $address['code'] = $value;
                    break;

                case 'city':
                case 'ort':
                    $address['city'] = $value;
                    break;
            }
        }

        $calculatedAddress = trim($address['street'] . PHP_EOL . $address['code'] . ' ' . $address['city']);
        $calculatedContact = trim(str_replace('  ', ' ', $names['title'] . ' ' . $names['first'] . ' ' . $names['middle'] . ' ' . $names['last']));

        if (!empty($calculatedAddress)) {
            $customer->setAddress($calculatedAddress);
        }

        if (!empty($calculatedContact)) {
            $customer->setContact($calculatedContact);
        }

        return $customer;
    }
}
