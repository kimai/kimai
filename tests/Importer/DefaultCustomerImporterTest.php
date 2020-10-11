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
use App\Importer\UnsupportedFormatException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Importer\DefaultCustomerImporter
 */
class DefaultCustomerImporterTest extends TestCase
{
    private function getSut(int $count = 1): DefaultCustomerImporter
    {
        $customerService = $this->createMock(CustomerService::class);
        $customerService->expects($this->exactly($count))->method('createNewCustomer')->willReturnCallback(
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

    public function testImportMissingName()
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Missing customer name, expected in column: "Name"');

        return $this->getSut(0)->convertEntryToCustomer(['sdfgsdfgsdfg' => 'sdfgsdfg']);
    }

    public function testImport()
    {
        $customer = $this->prepareCustomer([]);
        self::assertEquals('Test customer', $customer->getName());
    }

    public function testImportWithMultipleValues()
    {
        $customer = $this->prepareCustomer([
            'e mail' => 'test@example.com',
            'contact' => 'Foo Bar',
            'phone' => '0123 4567890',
            'mobile' => '111 354687',
            'fax' => '999 112233445566778899',
            'homepage' => 'www.example.com',
            'budget' => 1000.17,
            'time budget' => 3600,
            'meta.qwertz' => 'uztiuzgubhöklji7gl',
        ]);
        self::assertEquals('Test customer', $customer->getName());
        self::assertEquals(1000.17, $customer->getBudget());
        self::assertEquals(3600, $customer->getTimeBudget());
        self::assertEquals('0123 4567890', $customer->getPhone());
        self::assertEquals('111 354687', $customer->getMobile());
        self::assertEquals('999 112233445566778899', $customer->getFax());
        self::assertEquals('www.example.com', $customer->getHomepage());
        self::assertEquals('www.example.com', $customer->getHomepage());
        self::assertEquals('uztiuzgubhöklji7gl', $customer->getMetaField('qwertz')->getValue());
    }
}
