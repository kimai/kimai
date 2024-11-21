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
use App\Tests\Mocks\SystemConfigurationFactory;
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
            $dispatcher->method('dispatch')->willReturnCallback(function ($event) {
                return $event;
            });
        }

        if ($validator === null) {
            $validator = $this->createMock(ValidatorInterface::class);
            $validator->method('validate')->willReturn(new ConstraintViolationList());
        }

        if ($configuration === null) {
            $configuration = SystemConfigurationFactory::createStub([
                'defaults' => [
                    'customer' => [
                        'timezone' => 'Europe/Vienna',
                        'country' => 'IN',
                        'currency' => 'RUB',
                    ]
                ]
            ]);
        }

        return new CustomerService($repository, $configuration, $validator, $dispatcher);
    }

    public function testCannotSavePersistedCustomerAsNew(): void
    {
        $Customer = $this->createMock(Customer::class);
        $Customer->expects($this->once())->method('getId')->willReturn(1);

        $sut = $this->getSut();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create customer, already persisted');

        $sut->saveNewCustomer($Customer);
    }

    public function testSaveNewCustomerHasValidationError(): void
    {
        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($constraints);

        $sut = $this->getSut(null, $validator);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Validation Failed');

        $sut->saveNewCustomer(new Customer('foo'));
    }

    public function testUpdateDispatchesEvents(): void
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

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $sut->updateCustomer($Customer);
    }

    public function testCreateNewCustomerDispatchesEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if (!$event instanceof CustomerMetaDefinitionEvent && !$event instanceof CustomerCreateEvent) {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $customer = $sut->createNewCustomer('');

        self::assertEquals('Europe/Vienna', $customer->getTimezone());
        self::assertEquals('IN', $customer->getCountry());
        self::assertEquals('RUB', $customer->getCurrency());
    }

    public function testSaveNewCustomerDispatchesEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if (!$event instanceof CustomerCreatePreEvent && !$event instanceof CustomerCreatePostEvent) {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $Customer = new Customer('foo');
        $sut->saveNewCustomer($Customer);
    }
}
