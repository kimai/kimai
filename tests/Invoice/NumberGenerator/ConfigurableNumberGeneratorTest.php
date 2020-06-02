<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\NumberGenerator;

use App\Configuration\SystemConfiguration;
use App\Invoice\InvoiceModel;
use App\Invoice\NumberGenerator\ConfigurableNumberGenerator;
use App\Repository\InvoiceRepository;
use App\Tests\Invoice\DebugFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\NumberGenerator\ConfigurableNumberGenerator
 */
class ConfigurableNumberGeneratorTest extends TestCase
{
    private function getSut(string $format)
    {
        $config = $this->createMock(SystemConfiguration::class);
        $config->expects($this->any())
            ->method('find')
            ->willReturn($format);

        $repository = $this->createMock(InvoiceRepository::class);
        $repository
            ->expects($this->any())
            ->method('getCounterForAllTime')
            ->willReturn(1);
        $repository
            ->expects($this->any())
            ->method('getCounterForYear')
            ->willReturn(1);
        $repository
            ->expects($this->any())
            ->method('getCounterForMonth')
            ->willReturn(1);
        $repository
            ->expects($this->any())
            ->method('getCounterForDay')
            ->willReturn(1);

        return new ConfigurableNumberGenerator($repository, $config);
    }

    public function getTestData()
    {
        $invoiceDate = new \DateTime();

        return [
            // simple tests for single calls
            ['{date}', $invoiceDate->format('ymd'), $invoiceDate],
            ['{Y}', $invoiceDate->format('Y'), $invoiceDate],
            ['{y}', $invoiceDate->format('y'), $invoiceDate],
            ['{M}', $invoiceDate->format('m'), $invoiceDate],
            ['{m}', $invoiceDate->format('n'), $invoiceDate],
            ['{D}', $invoiceDate->format('d'), $invoiceDate],
            ['{d}', $invoiceDate->format('j'), $invoiceDate],
            ['{c}', '2', $invoiceDate],
            ['{cy}', '2', $invoiceDate],
            ['{cm}', '2', $invoiceDate],
            ['{cd}', '2', $invoiceDate],
            // number formatting (not testing the lower case versions, as the tests might break depending on the date)
            ['{date,10}', '0000' . $invoiceDate->format('ymd'), $invoiceDate],
            ['{date,a}', $invoiceDate->format('ymd'), $invoiceDate], // invalid formatter length
            ['{Y,6}', '00' . $invoiceDate->format('Y'), $invoiceDate],
            ['{M,3}', '0' . $invoiceDate->format('m'), $invoiceDate],
            ['{M,#}', $invoiceDate->format('m'), $invoiceDate], // invalid formatter length
            ['{D,3}', '0' . $invoiceDate->format('d'), $invoiceDate],
            ['{c,2}', '02', $invoiceDate],
            ['{cy,2}', '02', $invoiceDate],
            ['{cm,2}', '02', $invoiceDate],
            ['{cd,2}', '02', $invoiceDate],
            // mixing identifiers
            ['{Y}{cy}', $invoiceDate->format('Y') . '2', $invoiceDate],
            ['{Y}{cy}{m}', $invoiceDate->format('Y') . '2' . $invoiceDate->format('n'), $invoiceDate],
            ['{Y}-{cy}/{m}', $invoiceDate->format('Y') . '-2/' . $invoiceDate->format('n'), $invoiceDate],
            ['{Y}-{cy}/{m}', $invoiceDate->format('Y') . '-2/' . $invoiceDate->format('n'), $invoiceDate],
            ['{Y,5}/{cy,5}', '0' . $invoiceDate->format('Y') . '/00002', $invoiceDate],
            ['{Y,!}/{cy,o}', $invoiceDate->format('Y') . '/2', $invoiceDate], // invalid formatter length
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testGetInvoiceNumber(string $format, string $expectedInvoiceNumber, \DateTime $invoiceDate)
    {
        $sut = $this->getSut($format);
        $model = new InvoiceModel(new DebugFormatter());
        $model->setInvoiceDate($invoiceDate);
        $sut->setModel($model);

        $this->assertEquals($expectedInvoiceNumber, $sut->getInvoiceNumber());
        $this->assertEquals('default', $sut->getId());
    }
}
