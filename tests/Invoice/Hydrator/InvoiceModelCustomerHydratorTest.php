<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Hydrator;

use App\Invoice\Hydrator\InvoiceModelCustomerHydrator;
use App\Tests\Invoice\Renderer\RendererTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\Hydrator\InvoiceModelCustomerHydrator
 */
class InvoiceModelCustomerHydratorTest extends TestCase
{
    use RendererTestTrait;

    public function testHydrate()
    {
        $model = $this->getInvoiceModel();

        $sut = new InvoiceModelCustomerHydrator();

        $result = $sut->hydrate($model);
        $this->assertModelStructure($result);

        $model->setCustomer(null);
        $result = $sut->hydrate($model);
        self::assertEmpty($result);
    }

    protected function assertModelStructure(array $model)
    {
        $keys = [
            'customer.id',
            'customer.address',
            'customer.name',
            'customer.contact',
            'customer.company',
            'customer.vat',
            'customer.country',
            'customer.number',
            'customer.homepage',
            'customer.comment',
            'customer.email',
            'customer.fax',
            'customer.phone',
            'customer.mobile',
            'customer.meta.foo-customer',
        ];

        $givenKeys = array_keys($model);
        sort($keys);
        sort($givenKeys);

        $this->assertEquals($keys, $givenKeys);
    }
}
