<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Hydrator;

use App\Invoice\Hydrator\InvoiceModelDefaultHydrator;
use App\Tests\Invoice\Renderer\RendererTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\Hydrator\InvoiceModelDefaultHydrator
 */
class InvoiceModelDefaultHydratorTest extends TestCase
{
    use RendererTestTrait;

    public function testHydrate()
    {
        $model = $this->getInvoiceModel();

        $sut = new InvoiceModelDefaultHydrator();

        $result = $sut->hydrate($model);
        $this->assertModelStructure($result);
    }

    protected function assertModelStructure(array $model, $hasProject = true)
    {
        $keys = [
            'invoice.due_date',
            'invoice.date',
            'invoice.number',
            'invoice.currency',
            'invoice.currency_symbol',
            'invoice.vat',
            'invoice.language',
            'invoice.tax',
            'invoice.tax_nc',
            'invoice.tax_plain',
            'invoice.total_time',
            'invoice.duration_decimal',
            'invoice.total',
            'invoice.total_nc',
            'invoice.total_plain',
            'invoice.subtotal',
            'invoice.subtotal_nc',
            'invoice.subtotal_plain',
            'template.name',
            'template.company',
            'template.address',
            'template.title',
            'template.payment_terms',
            'template.due_days',
            'template.vat_id',
            'template.contact',
            'template.payment_details',
            'query.begin',
            'query.end',
            'query.day',
            'query.month',
            'query.month_number',
            'query.year',
            'query.begin_day',
            'query.begin_month',
            'query.begin_month_number',
            'query.begin_year',
            'query.end_day',
            'query.end_month',
            'query.end_month_number',
            'query.end_year',
        ];

        $givenKeys = array_keys($model);
        sort($keys);
        sort($givenKeys);

        $this->assertEquals($keys, $givenKeys);
    }
}
