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
            switch ($name) {
                case 'Organization':
                case 'Firma':
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
                case 'Customer number':
                case 'Kundennummer':
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
            switch ($name) {
                case 'Department':
                case 'Abteilung':

                case 'Salutation':
                case 'Briefanrede':

                case 'State':
                case 'Bundesland':

                case 'IBAN':
                case 'BIC':

                case 'SEPA Mandate ID':
                case 'SEPA Mandat':
                    // not supported in Kimai
                    break;

                case 'Organization':
                case 'Firma':
                    $customer->setCompany($value);
                    break;

                case 'E-Mail':
                    if (!empty($value)) {
                        $customer->setEmail($value);
                    }
                    break;

                case 'Country':
                case 'Land':
                    if (!empty($value)) {
                        $customer->setCountry($value);
                    }
                    break;

                case 'Customer number':
                case 'Kundennummer':
                    if (!empty($value)) {
                        $customer->setNumber($value);
                    }
                    break;

                case 'Tax-ID':
                case 'Umsatzsteuer':
                    if (!empty($value)) {
                        $customer->setVatId($value);
                    }
                    break;

                case 'Note':
                case 'Notiz':
                    if (!empty($value)) {
                        $customer->setComment(strip_tags($value));
                    }
                break;

                case 'Title':
                case 'Titel':
                    $names['title'] = $value;
                    break;

                case 'First name':
                case 'Vorname':
                    $names['first'] = $value;
                    break;

                case 'Middle name':
                case 'Zweiter Vorname':
                    $names['middle'] = $value;
                    break;

                case 'Last name':
                case 'Nachname':
                    $names['last'] = $value;
                    break;

                case 'Street':
                case 'Straße':
                    $address['street'] = $value;
                    break;

                case 'ZIP':
                case 'PLZ':
                    $address['code'] = $value;
                    break;

                case 'City':
                case 'Ort':
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
