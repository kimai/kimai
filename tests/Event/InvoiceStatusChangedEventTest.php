<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Invoice;
use App\Event\InvoiceStatusChangedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceStatusChangedEvent::class)]
class InvoiceStatusChangedEventTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $invoice = new Invoice();

        $sut = new InvoiceStatusChangedEvent($invoice, Invoice::STATUS_PAID);

        self::assertSame($invoice, $sut->getInvoice());
        self::assertSame(Invoice::STATUS_PAID, $sut->getStatusBefore());
    }
}
