<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Hydrator;

use App\Customer\CustomerStatisticService;
use App\Invoice\Hydrator\InvoiceModelCustomerHydrator;
use App\Tests\Invoice\Renderer\RendererTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceModelCustomerHydrator::class)]
class InvoiceModelCustomerHydratorTest extends TestCase
{
    use RendererTestTrait;

    public function testHydrate(): void
    {
        $model = $this->getInvoiceModel();

        $sut = new InvoiceModelCustomerHydrator($this->createMock(CustomerStatisticService::class));

        $result = $sut->hydrate($model);
        $this->assertModelStructure($result);

        $result = $sut->hydrate($model);

        $this->assertModelStructure($result);

        self::assertEquals([
            'customer.id' => null,
            'customer.address' => "Foo\nStreet\n1111 City",
            'customer.address_line1' => '',
            'customer.address_line2' => '',
            'customer.address_line3' => '',
            'customer.buyer_reference' => '',
            'customer.city' => '',
            'customer.postcode' => '',
            'customer.name' => 'customer,with/special#name',
            'customer.contact' => '',
            'customer.company' => '',
            'customer.vat' => '',
            'customer.vat_id' => '',
            'customer.number' => '',
            'customer.country' => 'AT',
            'customer.country_name' => 'Austria',
            'customer.homepage' => '',
            'customer.comment' => '',
            'customer.email' => '',
            'customer.fax' => '',
            'customer.phone' => '',
            'customer.mobile' => '',
            'customer.invoice_text' => '',
            'customer.budget_open' => '€0.00',
            'customer.budget_open_plain' => 0.0,
            'customer.time_budget_open' => '0.00',
            'customer.time_budget_open_plain' => 0,
            'customer.meta.foo-customer' => 'bar-customer',
        ], $result);
    }

    protected function assertModelStructure(array $model): void
    {
        $keys = [
            'customer.id',
            'customer.address',
            'customer.address_line1',
            'customer.address_line2',
            'customer.address_line3',
            'customer.buyer_reference',
            'customer.city',
            'customer.postcode',
            'customer.name',
            'customer.contact',
            'customer.company',
            'customer.vat',
            'customer.vat_id',
            'customer.country',
            'customer.country_name',
            'customer.number',
            'customer.homepage',
            'customer.comment',
            'customer.email',
            'customer.fax',
            'customer.phone',
            'customer.mobile',
            'customer.meta.foo-customer',
            'customer.budget_open',
            'customer.budget_open_plain',
            'customer.time_budget_open',
            'customer.time_budget_open_plain',
            'customer.invoice_text',
        ];

        $givenKeys = array_keys($model);
        sort($keys);
        sort($givenKeys);

        self::assertEquals($keys, $givenKeys);
    }
}
