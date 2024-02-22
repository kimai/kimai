<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Invoice;
use App\Event\InvoiceDeleteEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\InvoiceDeleteEvent
 */
class InvoiceDeleteEventTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $invoice = new Invoice();

        $sut = new InvoiceDeleteEvent($invoice);

        self::assertSame($invoice, $sut->getInvoice());
    }
}
