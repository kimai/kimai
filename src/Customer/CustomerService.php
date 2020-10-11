<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Customer;

use App\Configuration\SystemConfiguration;
use App\Entity\Customer;
use App\Event\CustomerCreateEvent;
use App\Event\CustomerCreatePostEvent;
use App\Event\CustomerCreatePreEvent;
use App\Event\CustomerMetaDefinitionEvent;
use App\Event\CustomerUpdatePostEvent;
use App\Event\CustomerUpdatePreEvent;
use App\Repository\CustomerRepository;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerService
{
    private $repository;
    private $dispatcher;
    private $validator;
    private $configuration;

    public function __construct(CustomerRepository $customerRepository, SystemConfiguration $configuration, ValidatorInterface $validator, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $customerRepository;
        $this->dispatcher = $dispatcher;
        $this->validator = $validator;
        $this->configuration = $configuration;
    }

    private function getDefaultTimezone(): string
    {
        if (null === ($timezone = $this->configuration->getCustomerDefaultTimezone())) {
            $timezone = date_default_timezone_get();
        }

        return $timezone;
    }

    public function createNewCustomer(): Customer
    {
        $customer = new Customer();
        $customer->setTimezone($this->getDefaultTimezone());
        $customer->setCountry($this->configuration->getCustomerDefaultCountry());
        $customer->setCurrency($this->configuration->getCustomerDefaultCurrency());

        $this->dispatcher->dispatch(new CustomerMetaDefinitionEvent($customer));
        $this->dispatcher->dispatch(new CustomerCreateEvent($customer));

        return $customer;
    }

    public function saveNewCustomer(Customer $customer): Customer
    {
        if (null !== $customer->getId()) {
            throw new InvalidArgumentException('Cannot create customer, already persisted');
        }

        $this->validateCustomer($customer);

        $this->dispatcher->dispatch(new CustomerCreatePreEvent($customer));
        $this->repository->saveCustomer($customer);
        $this->dispatcher->dispatch(new CustomerCreatePostEvent($customer));

        return $customer;
    }

    /**
     * @param Customer $customer
     * @param string[] $groups
     * @throws ValidationFailedException
     */
    private function validateCustomer(Customer $customer, array $groups = []): void
    {
        $errors = $this->validator->validate($customer, null, $groups);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors, 'Validation Failed');
        }
    }

    public function updateCustomer(Customer $customer): Customer
    {
        $this->validateCustomer($customer);

        $this->dispatcher->dispatch(new CustomerUpdatePreEvent($customer));
        $this->repository->saveCustomer($customer);
        $this->dispatcher->dispatch(new CustomerUpdatePostEvent($customer));

        return $customer;
    }

    public function findCustomerByName(string $name): ?Customer
    {
        return $this->repository->findOneBy(['name' => $name]);
    }

    public function findCustomerByNumber(string $number): ?Customer
    {
        return $this->repository->findOneBy(['number' => $number]);
    }
}
