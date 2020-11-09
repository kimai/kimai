<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Customer;

use App\Configuration\SystemConfiguration;
use App\Customer\CustomerService;
use App\Entity\Customer;
use App\Event\CustomerCreateEvent;
use App\Event\CustomerCreatePostEvent;
use App\Event\CustomerCreatePreEvent;
use App\Event\CustomerMetaDefinitionEvent;
use App\Event\CustomerUpdatePostEvent;
use App\Event\CustomerUpdatePreEvent;
use App\Repository\CustomerRepository;
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Customer\CustomerService
 */
class CustomerServiceTest extends TestCase
{
    private function getSut(
        ?EventDispatcherInterface $dispatcher = null,
        ?ValidatorInterface $validator = null,
        ?CustomerRepository $repository = null,
        ?SystemConfiguration $configuration = null
    ): CustomerService {
        if ($repository === null) {
            $repository = $this->createMock(CustomerRepository::class);
        }

        if ($dispatcher === null) {
            $dispatcher = $this->createMock(EventDispatcherInterface::class);
        }

        if ($validator === null) {
            $validator = $this->createMock(ValidatorInterface::class);
            $validator->method('validate')->willReturn(new ConstraintViolationList());
        }

        if ($configuration === null) {
            $configuration = $this->createMock(SystemConfiguration::class);
            $configuration->method('getCustomerDefaultTimezone')->willReturn('Europe/Vienna');
            $configuration->method('getCustomerDefaultCountry')->willReturn('IN');
            $configuration->method('getCustomerDefaultCurrency')->willReturn('RUB');
        }

        $service = new CustomerService($repository, $configuration, $validator, $dispatcher);

        return $service;
    }

    public function testCannotSavePersistedCustomerAsNew()
    {
        $Customer = $this->createMock(Customer::class);
        $Customer->expects($this->once())->method('getId')->willReturn(1);

        $sut = $this->getSut();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create customer, already persisted');

        $sut->saveNewCustomer($Customer);
    }

    public function testSaveNewCustomerHasValidationError()
    {
        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($constraints);

        $sut = $this->getSut(null, $validator);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Validation Failed');

        $sut->saveNewCustomer(new Customer());
    }

    public function testUpdateDispatchesEvents()
    {
        $Customer = $this->createMock(Customer::class);
        $Customer->method('getId')->willReturn(1);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) use ($Customer) {
            if ($event instanceof CustomerUpdatePostEvent) {
                self::assertSame($Customer, $event->getCustomer());
            } elseif ($event instanceof CustomerUpdatePreEvent) {
                self::assertSame($Customer, $event->getCustomer());
            } else {
                $this->fail('Invalid event received');
            }
        });

        $sut = $this->getSut($dispatcher);

        $sut->updateCustomer($Customer);
    }

    public function testCreateNewCustomerDispatchesEvents()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if ($event instanceof CustomerMetaDefinitionEvent) {
                self::assertInstanceOf(Customer::class, $event->getEntity());
            } elseif ($event instanceof CustomerCreateEvent) {
                self::assertInstanceOf(Customer::class, $event->getCustomer());
            } else {
                $this->fail('Invalid event received');
            }
        });

        $sut = $this->getSut($dispatcher);

        $customer = $sut->createNewCustomer();

        self::assertInstanceOf(Customer::class, $customer);
        self::assertEquals('Europe/Vienna', $customer->getTimezone());
        self::assertEquals('IN', $customer->getCountry());
        self::assertEquals('RUB', $customer->getCurrency());
    }

    public function testSaveNewCustomerDispatchesEvents()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if ($event instanceof CustomerCreatePreEvent) {
                self::assertInstanceOf(Customer::class, $event->getCustomer());
            } elseif ($event instanceof CustomerCreatePostEvent) {
                self::assertInstanceOf(Customer::class, $event->getCustomer());
            } else {
                $this->fail('Invalid event received');
            }
        });

        $sut = $this->getSut($dispatcher);

        $Customer = new Customer();
        $sut->saveNewCustomer($Customer);
    }
}
