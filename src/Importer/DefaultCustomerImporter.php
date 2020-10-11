<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use App\Entity\Customer;
use App\Entity\CustomerMeta;

final class DefaultCustomerImporter extends AbstractCustomerImporter
{
    protected function mapEntryToCustomer(Customer $customer, array $row)
    {
        foreach ($row as $name => $value) {
            switch (strtolower($name)) {
                case 'name':
                    $customer->setName(substr($value, 0, 149));
                    if (empty($customer->getCompany())) {
                        $customer->setCompany($value);
                    }
                    break;

                case 'company':
                case 'company-name':
                case 'company name':
                    $customer->setCompany($value);
                    break;

                case 'email':
                case 'e-mail':
                case 'e mail':
                    if (!empty($value)) {
                        $customer->setEmail($value);
                    }
                    break;

                case 'country':
                    if (!empty($value)) {
                        $customer->setCountry($value);
                    }
                    break;

                case 'number':
                case 'account':
                case 'customer number':
                case 'customer account':
                    if (!empty($value)) {
                        $customer->setNumber($value);
                    }
                    break;

                case 'vat':
                case 'vat-id':
                case 'vat id':
                case 'tax-id':
                case 'tax id':
                    if (!empty($value)) {
                        $customer->setVatId($value);
                    }
                    break;

                case 'comment':
                case 'description':
                    if (!empty($value)) {
                        $customer->setComment($value);
                    }
                break;

                case 'address':
                    if (!empty($value)) {
                        $customer->setAddress($value);
                    }
                break;

                case 'contact':
                    if (!empty($value)) {
                        $customer->setContact($value);
                    }
                break;

                case 'currency':
                    if (!empty($value)) {
                        $customer->setCurrency($value);
                    }
                break;

                case 'timezone':
                    if (!empty($value)) {
                        $customer->setTimezone($value);
                    }
                break;

                case 'phone':
                    if (!empty($value)) {
                        $customer->setPhone($value);
                    }
                break;

                case 'mobile':
                    if (!empty($value)) {
                        $customer->setMobile($value);
                    }
                break;

                case 'fax':
                    if (!empty($value)) {
                        $customer->setFax($value);
                    }
                break;

                case 'homepage':
                    if (!empty($value)) {
                        $customer->setHomepage($value);
                    }
                break;

                case 'color':
                    if (!empty($value)) {
                        $customer->setColor($value);
                    }
                break;

                case 'visible':
                    if ($value !== '') {
                        $customer->setVisible((bool) $value);
                    }
                break;

                case 'budget':
                    if (!empty($value)) {
                        $customer->setBudget($value);
                    }
                    break;

                case 'time budget':
                case 'time-budget':
                    if (!empty($value)) {
                        $customer->setTimeBudget($value);
                    }
                    break;

                default:
                    if (stripos($name, 'meta.') === 0) {
                        $tmpName = str_replace('meta.', '', $name);
                        $meta = new CustomerMeta();
                        $meta->setIsVisible(true);
                        $meta->setName($tmpName);
                        $meta->setValue($value);
                        $customer->setMetaField($meta);
                    }
                break;
            }
        }

        return $customer;
    }
}
