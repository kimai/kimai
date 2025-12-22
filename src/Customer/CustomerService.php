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
use App\Event\CustomerDeleteEvent;
use App\Event\CustomerMetaDefinitionEvent;
use App\Event\CustomerUpdatePostEvent;
use App\Event\CustomerUpdatePreEvent;
use App\Repository\CustomerRepository;
use App\Repository\Query\CustomerQuery;
use App\Utils\NumberGenerator;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $repository,
        private readonly SystemConfiguration $configuration,
        private readonly ValidatorInterface $validator,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    private function getDefaultTimezone(): string
    {
        if (null === ($timezone = $this->configuration->getCustomerDefaultTimezone())) {
            $timezone = date_default_timezone_get();
        }

        return $timezone;
    }

    public function loadMetaFields(Customer $customer): void
    {
        $this->dispatcher->dispatch(new CustomerMetaDefinitionEvent($customer));
    }

    public function createNewCustomer(string $name): Customer
    {
        $customer = new Customer($name);
        $customer->setTimezone($this->getDefaultTimezone());
        $customer->setCountry($this->configuration->getCustomerDefaultCountry());
        $customer->setCurrency($this->configuration->getDefaultCurrency());
        $customer->setNumber($this->calculateNextCustomerNumber());

        $this->loadMetaFields($customer);
        $this->dispatcher->dispatch(new CustomerCreateEvent($customer));

        return $customer;
    }

    public function saveCustomer(Customer $customer): Customer
    {
        if ($customer->isNew()) {
            return $this->saveNewCustomer($customer); // @phpstan-ignore method.deprecated
        } else {
            return $this->updateCustomer($customer); // @phpstan-ignore method.deprecated
        }
    }

    /**
     * @deprecated since 2.35 - use saveCustomer() instead
     */
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

    public function deleteCustomer(Customer $customer, ?Customer $replace = null): void
    {
        $this->dispatcher->dispatch(new CustomerDeleteEvent($customer, $replace));
        $this->repository->deleteCustomer($customer, $replace);
    }

    /**
     * @param string[] $groups
     * @throws ValidationFailedException
     */
    private function validateCustomer(Customer $customer, array $groups = []): void
    {
        $errors = $this->validator->validate($customer, null, $groups);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors);
        }
    }

    /**
     * @deprecated since 2.35 - use saveCustomer() instead
     */
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

    /**
     * @return iterable<Customer>
     */
    public function findCustomer(CustomerQuery $query): iterable
    {
        return $this->repository->getCustomersForQuery($query);
    }

    public function countCustomer(bool $visible = true): int
    {
        return $this->repository->countCustomer($visible);
    }

    private function calculateNextCustomerNumber(): ?string
    {
        $format = $this->configuration->find('customer.number_format');
        if (empty($format) || !\is_string($format)) {
            return null;
        }

        // we cannot use max(number) because a varchar column returns unexpected results
        $start = $this->repository->countCustomer();
        $i = 0;
        $createDate = new \DateTimeImmutable();

        do {
            $start++;

            $numberGenerator = new NumberGenerator($format, function (string $originalFormat, string $format, int $increaseBy) use ($start, $createDate): string|int {
                return match ($format) {
                    'Y' => $createDate->format('Y'),
                    'y' => $createDate->format('y'),
                    'M' => $createDate->format('m'),
                    'm' => $createDate->format('n'),
                    'D' => $createDate->format('d'),
                    'd' => $createDate->format('j'),
                    'YY' => (int) $createDate->format('Y') + $increaseBy,
                    'yy' => (int) $createDate->format('y') + $increaseBy,
                    'MM' => (int) $createDate->format('m') + $increaseBy,
                    'DD' => (int) $createDate->format('d') + $increaseBy,
                    'cc' => $start + $increaseBy,
                    default => $originalFormat,
                };
            });

            $number = $numberGenerator->getNumber();
            $customer = $this->findCustomerByNumber($number);
        } while ($customer !== null && $i++ < 100);

        if ($customer !== null) {
            return null;
        }

        return $number;
    }
}
