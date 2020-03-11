<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\NumberGenerator;

use App\Invoice\InvoiceModel;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Tests\Invoice\DebugFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\NumberGenerator\DateNumberGenerator
 */
class DateNumberGeneratorTest extends TestCase
{
    public function testGetInvoiceNumber()
    {
        $sut = new DateNumberGenerator();
        $sut->setModel(new InvoiceModel(new DebugFormatter()));

        $this->assertEquals(date('ymd'), $sut->getInvoiceNumber());
        $this->assertEquals('date', $sut->getId());
    }
}
