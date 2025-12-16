<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Hydrator;

use App\Invoice\Hydrator\InvoiceModelIssuerHydrator;
use App\Tests\Invoice\Renderer\RendererTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceModelIssuerHydrator::class)]
class InvoiceModelIssuerHydratorTest extends TestCase
{
    use RendererTestTrait;

    public function testHydrate(): void
    {
        $model = $this->getInvoiceModel();

        $sut = new InvoiceModelIssuerHydrator();

        $result = $sut->hydrate($model);
        $this->assertModelStructure($result);

        $result = $sut->hydrate($model);

        $this->assertModelStructure($result);

        self::assertEquals([
            'issuer.id' => null,
            'issuer.address' => "Foo\nStreet\n1111 City",
            'issuer.address_line1' => '',
            'issuer.address_line2' => '',
            'issuer.address_line3' => '',
            'issuer.buyer_reference' => '',
            'issuer.city' => '',
            'issuer.postcode' => '',
            'issuer.name' => 'customer,with/special#name',
            'issuer.contact' => '',
            'issuer.company' => '',
            'issuer.vat_id' => '',
            'issuer.number' => '',
            'issuer.country' => 'AT',
            'issuer.country_name' => 'Austria',
            'issuer.homepage' => '',
            'issuer.comment' => '',
            'issuer.email' => '',
            'issuer.fax' => '',
            'issuer.phone' => '',
            'issuer.mobile' => '',
            'issuer.invoice_text' => '',
            'issuer.meta.foo-customer' => 'bar-customer',
        ], $result);
    }

    protected function assertModelStructure(array $model): void
    {
        $keys = [
            'issuer.id',
            'issuer.address',
            'issuer.address_line1',
            'issuer.address_line2',
            'issuer.address_line3',
            'issuer.buyer_reference',
            'issuer.city',
            'issuer.postcode',
            'issuer.name',
            'issuer.contact',
            'issuer.company',
            'issuer.vat_id',
            'issuer.country',
            'issuer.country_name',
            'issuer.number',
            'issuer.homepage',
            'issuer.comment',
            'issuer.email',
            'issuer.fax',
            'issuer.phone',
            'issuer.mobile',
            'issuer.meta.foo-customer',
            'issuer.invoice_text',
        ];

        $givenKeys = array_keys($model);
        sort($keys);
        sort($givenKeys);

        self::assertEquals($keys, $givenKeys);
    }
}
