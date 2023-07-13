<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\NumberGenerator;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\NumberGenerator\DateNumberGenerator
 */
class DateNumberGeneratorTest extends TestCase
{
    private function getSut(bool $hasInitialInvoice, bool $followingInvoices)
    {
        $repository = $this->createMock(InvoiceRepository::class);
        $repository
            ->expects($this->any())
            ->method('hasInvoice')
            ->willReturnCallback(function ($number) use ($hasInitialInvoice, $followingInvoices) {
                if (stripos($number, '-') === false) {
                    return $hasInitialInvoice;
                }

                return $followingInvoices;
            });

        return new DateNumberGenerator($repository);
    }

    public function testGetInvoiceNumber()
    {
        $sut = $this->getSut(false, false);
        $sut->setModel((new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), new Customer('foo'), new InvoiceTemplate(), new InvoiceQuery()));

        $this->assertEquals(date('ymd'), $sut->getInvoiceNumber());
        $this->assertEquals('date', $sut->getId());
    }

    public function testGetInvoiceNumberWithExisting()
    {
        $sut = $this->getSut(true, false);
        $sut->setModel((new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), new Customer('foo'), new InvoiceTemplate(), new InvoiceQuery()));

        $this->assertEquals(date('ymd-01'), $sut->getInvoiceNumber());
        $this->assertEquals('date', $sut->getId());
    }

    public function testGetInvoiceNumberWithManyExisting()
    {
        $sut = $this->getSut(true, true);
        $sut->setModel((new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), new Customer('foo'), new InvoiceTemplate(), new InvoiceQuery()));

        $this->assertEquals(date('ymd-99'), $sut->getInvoiceNumber());
        $this->assertEquals('date', $sut->getId());
    }
}
