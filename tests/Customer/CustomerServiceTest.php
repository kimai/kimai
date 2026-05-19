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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(CustomerService::class)]
class CustomerServiceTest extends TestCase
{
    private function getSut(
        ?EventDispatcherInterface $dispatcher = null,
        ?ValidatorInterface $validator = null,
        ?SystemConfiguration $configuration = null
    ): CustomerService {
        $repository = $this->createMock(CustomerRepository::class);

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

    public function testSaveNewCustomerHasValidationError(): void
    {
        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($constraints);

        $sut = $this->getSut(null, $validator);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Validation Failed');

        $sut->saveCustomer(new Customer('foo'));
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

        $sut->saveCustomer($Customer);
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
        $sut->saveCustomer($Customer);
    }

    #[DataProvider('getTestData')]
    public function testCustomerNumber(string $format, int|string $expected): void
    {
        $configuration = SystemConfigurationFactory::createStub([
            'defaults' => [
                'customer' => [
                    'timezone' => 'Europe/Vienna',
                    'country' => 'IN',
                    'currency' => 'RUB',
                ]
            ],
            'customer' => [
                'number_format' => $format
            ]
        ]);

        $sut = $this->getSut(null, null, $configuration);
        $customer = $sut->createNewCustomer('Test');

        self::assertEquals((string) $expected, $customer->getNumber());
    }

    /**
     * @return array<int, array<int, string|\DateTime|int>>
     */
    public static function getTestData(): array
    {
        $dateTime = new \DateTime();

        $yearLong = (int) $dateTime->format('Y');
        $yearShort = (int) $dateTime->format('y');
        $monthLong = $dateTime->format('m');
        $monthShort = (int) $dateTime->format('n');
        $dayLong = $dateTime->format('d');
        $dayShort = (int) $dateTime->format('j');

        return [
            // simple tests for single calls
            ['{cc,1}', '2'],
            ['{cc,2}', '02'],
            ['{cc,3}', '002'],
            ['{cc,4}', '0002'],
            ['{Y}', $yearLong],
            ['{y}', $yearShort],
            ['{M}', $monthLong],
            ['{m}', $monthShort],
            ['{D}', $dayLong],
            ['{d}', $dayShort],
            // number formatting (not testing the lower case versions, as the tests might break depending on the date)
            ['{Y,6}', '00' . $yearLong],
            ['{M,3}', '0' . $monthLong],
            ['{D,3}', '0' . $dayLong],
            // increment dates
            ['{YY}', $yearLong + 1],
            ['{YY+1}', $yearLong + 1],
            ['{YY+2}', $yearLong + 2],
            ['{YY+3}', $yearLong + 3],
            ['{YY-1}', $yearLong - 1],
            ['{YY-2}', $yearLong - 2],
            ['{YY-3}', $yearLong - 3],
            ['{yy}', $yearShort + 1],
            ['{yy+1}', $yearShort + 1],
            ['{yy+2}', $yearShort + 2],
            ['{yy+3}', $yearShort + 3],
            ['{yy-1}', $yearShort - 1],
            ['{yy-2}', $yearShort - 2],
            ['{yy-3}', $yearShort - 3],
            ['{MM}', $monthShort + 1], // cast to int removes leading zero
            ['{MM+1}', $monthShort + 1], // cast to int removes leading zero
            ['{MM+2}', $monthShort + 2], // cast to int removes leading zero
            ['{MM+3}', $monthShort + 3], // cast to int removes leading zero
            ['{DD}', $dayShort + 1], // cast to int removes leading zero
            ['{DD+1}', $dayShort + 1], // cast to int removes leading zero
            ['{DD+2}', $dayShort + 2], // cast to int removes leading zero
            ['{DD+3}', $dayShort + 3], // cast to int removes leading zero
        ];
    }
}
