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
        $timestamp = time();

        return [
            // simple tests for single calls
            ['{date}', date('ymd'), $timestamp],
            ['{Y}', date('Y'), $timestamp],
            ['{y}', date('y'), $timestamp],
            ['{M}', date('m'), $timestamp],
            ['{m}', date('n'), $timestamp],
            ['{D}', date('d'), $timestamp],
            ['{d}', date('j'), $timestamp],
            ['{c}', '2', $timestamp],
            ['{cy}', '2', $timestamp],
            ['{cm}', '2', $timestamp],
            ['{cd}', '2', $timestamp],
            // number formatting (not testing the lower case versions, as the tests might break depending on the date)
            ['{date,10}', '0000' . date('ymd'), $timestamp],
            ['{Y,6}', '00' . date('Y'), $timestamp],
            ['{M,3}', '0' . date('m'), $timestamp],
            ['{D,3}', '0' . date('d'), $timestamp],
            ['{c,2}', '02', $timestamp],
            ['{cy,2}', '02', $timestamp],
            ['{cm,2}', '02', $timestamp],
            ['{cd,2}', '02', $timestamp],
            // mixing identifiers
            ['{Y}{cy}', date('Y') . '2', $timestamp],
            ['{Y}{cy}{m}', date('Y') . '2' . date('n'), $timestamp],
            ['{Y}-{cy}/{m}', date('Y') . '-2/' . date('n'), $timestamp],
            ['{Y}-{cy}/{m}', date('Y') . '-2/' . date('n'), $timestamp],
            ['{Y,5}/{cy,5}', '0' . date('Y') . '/00002', $timestamp],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testGetInvoiceNumber(string $format, string $expectedInvoiceNumber, int $timestamp)
    {
        $sut = $this->getSut($format);
        $model = new InvoiceModel(new DebugFormatter());
        $model->setInvoiceDate((new \DateTime())->setTimestamp($timestamp));
        $sut->setModel($model);

        $this->assertEquals($expectedInvoiceNumber, $sut->getInvoiceNumber());
        $this->assertEquals('default', $sut->getId());
    }
}
