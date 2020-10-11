<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Importer;

use App\Customer\CustomerService;
use App\Entity\Customer;
use App\Importer\DefaultCustomerImporter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Importer\DefaultCustomerImporter
 */
class DefaultCustomerImporterTest extends TestCase
{
    private function getSut(): DefaultCustomerImporter
    {
        $customerService = $this->createMock(CustomerService::class);
        $customerService->expects($this->once())->method('createNewCustomer')->willReturnCallback(
            function () {
                return new Customer();
            }
        );

        $sut = new DefaultCustomerImporter($customerService);

        return $sut;
    }

    private function getDefaultImport(): array
    {
        return [
            'name' => 'Test customer',
        ];
    }

    private function prepareCustomer(array $values = []): Customer
    {
        $sut = $this->getSut();

        $import = array_merge($this->getDefaultImport(), $values);

        return $sut->convertEntryToCustomer($import);
    }

    public function testImport()
    {
        $customer = $this->prepareCustomer([]);
        self::assertEquals('Test customer', $customer->getName());
    }

    public function testImport2()
    {
        $customer = $this->prepareCustomer([]);
        self::assertEquals('Test customer', $customer->getName());
    }
}
