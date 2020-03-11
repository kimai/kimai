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
        return [
            // simple tests for single calls
            ['{date}', date('ymd')],
            ['{Y}', date('Y')],
            ['{y}', date('y')],
            ['{M}', date('m')],
            ['{m}', date('n')],
            ['{D}', date('d')],
            ['{d}', date('j')],
            ['{c}', '2'],
            ['{cy}', '2'],
            ['{cm}', '2'],
            ['{cd}', '2'],
            // number formatting (not testing the lower case versions, as the tests might break depending on the date)
            ['{date,10}', '0000' . date('ymd')],
            ['{Y,6}', '00' . date('Y')],
            ['{M,3}', '0' . date('m')],
            ['{D,3}', '0' . date('d')],
            ['{c,2}', '02'],
            ['{cy,2}', '02'],
            ['{cm,2}', '02'],
            ['{cd,2}', '02'],
            // mixing identifiers
            ['{Y}{cy}', date('Y') . '2'],
            ['{Y}{cy}{m}', date('Y') . '2' . date('n')],
            ['{Y}-{cy}/{m}', date('Y') . '-2/' . date('n')],
            ['{Y}-{cy}/{m}', date('Y') . '-2/' . date('n')],
            ['{Y,5}/{cy,5}', '0' . date('Y') . '/00002'],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testGetInvoiceNumber(string $format, string $expectedInvoiceNumber)
    {
        $sut = $this->getSut($format);
        $sut->setModel(new InvoiceModel(new DebugFormatter()));

        $this->assertEquals($expectedInvoiceNumber, $sut->getInvoiceNumber());
        $this->assertEquals('default', $sut->getId());
    }
}
