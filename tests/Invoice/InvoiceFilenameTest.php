<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Invoice\InvoiceFilename;
use App\Invoice\InvoiceModel;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\InvoiceFilename
 */
class InvoiceFilenameTest extends TestCase
{
    public function testInvoiceFilename()
    {
        $customer = new Customer();
        $template = new InvoiceTemplate();

        $model = new InvoiceModel(new DebugFormatter());
        $model->setNumberGenerator(new DateNumberGenerator());
        $model->setTemplate($template);
        $model->setCustomer($customer);

        $datePrefix = date('ymd');

        $sut = new InvoiceFilename($model);

        self::assertEquals($datePrefix, $sut->getFilename());
        self::assertEquals($datePrefix, (string) $sut);

        $customer->setName('foo');
        $sut = new InvoiceFilename($model);

        self::assertEquals($datePrefix . '-foo', $sut->getFilename());
        self::assertEquals($datePrefix . '-foo', (string) $sut);

        $customer->setCompany('barß / laölala # ldksjf 123');
        $sut = new InvoiceFilename($model);

        self::assertEquals($datePrefix . '-barss_laolala_ldksjf123', $sut->getFilename());
        self::assertEquals($datePrefix . '-barss_laolala_ldksjf123', (string) $sut);

        $customer->setCompany('까깨꺄꺠꺼께껴꼐꼬꽈sssss');
        $sut = new InvoiceFilename($model);
        self::assertEquals($datePrefix . '-kkakkaekkyakkyaekkeokkekkyeokkyekkokkwasssss', $sut->getFilename());

        $customer->setCompany('\"#+ß.!$%&/()=?\\n=/*-+´_<>@' . "\n");
        $sut = new InvoiceFilename($model);
        self::assertEquals($datePrefix . '-ss_n', $sut->getFilename());
    }
}
