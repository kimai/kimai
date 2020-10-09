<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use App\Customer\CustomerService;
use App\Entity\Customer;

abstract class AbstractCustomerImporter implements CustomerImporterInterface
{
    private $customerService;

    public function __construct(CustomerService $repository)
    {
        $this->customerService = $repository;
    }

    protected function findCustomerByName(string $name): ?Customer
    {
        return $this->customerService->findCustomerByName($name);
    }

    protected function findCustomerByNumber(string $number): ?Customer
    {
        return $this->customerService->findCustomerByNumber($number);
    }

    public function convertEntryToCustomer(array $entry): Customer
    {
        $customer = $this->findCustomer($entry);

        $this->mapEntryToCustomer($customer, $entry);

        return $customer;
    }

    protected function createNewCustomer(string $name): Customer
    {
        $customer = $this->customerService->createNewCustomer();
        $customer->setName(substr($name, 0, 149));

        return $customer;
    }

    protected function findCustomer(array $entry): ?Customer
    {
        $name = $this->findCustomerName($entry);
        $customer = $this->findCustomerByName($name);

        if ($customer === null) {
            $number = $this->findCustomerNumber($entry);
            if ($number !== null) {
                $customer = $this->findCustomerByNumber($number);
            }
        }

        if ($customer === null) {
            $customer = $this->createNewCustomer($name);
        }

        return $customer;
    }

    /**
     * Find the unique customer name inside $entry.
     *
     * @param array $entry
     * @return string
     * @throws UnsupportedFormatException
     */
    protected function findCustomerName(array $entry): string
    {
        foreach ($entry as $name => $value) {
            switch (strtolower($name)) {
                case 'name':
                    if (!empty($value)) {
                        return $value;
                    }
            }
        }

        throw new UnsupportedFormatException('Missing customer name, expected in column: "Name"');
    }

    /**
     * Find the unique customer number inside $entry.
     *
     * @param array $entry
     * @return string
     * @throws UnsupportedFormatException
     */
    protected function findCustomerNumber(array $entry): ?string
    {
        foreach ($entry as $name => $value) {
            switch (strtolower($name)) {
                case 'number':
                case 'account':
                    if (!empty($value)) {
                        return $value;
                    }
            }
        }

        return null;
    }

    /**
     * Applies all supported values from $entry to $customer.
     *
     * @param Customer $customer
     * @param array $entry
     * @return mixed
     */
    abstract protected function mapEntryToCustomer(Customer $customer, array $entry);
}
