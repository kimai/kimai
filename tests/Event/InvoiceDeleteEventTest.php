<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Invoice;
use App\Event\AbstractInvoiceEvent;
use App\Event\InvoiceDeleteEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceDeleteEvent::class)]
#[CoversClass(AbstractInvoiceEvent::class)]
class InvoiceDeleteEventTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $invoice = new Invoice();

        $sut = new InvoiceDeleteEvent($invoice);

        self::assertSame($invoice, $sut->getInvoice());
    }
}
