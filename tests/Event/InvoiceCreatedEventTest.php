<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceTemplate;
use App\Event\InvoiceCreatedEvent;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\InvoiceCreatedEvent
 */
class InvoiceCreatedEventTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $invoice = new Invoice();
        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), new Customer('foo'), new InvoiceTemplate(), new InvoiceQuery());

        $sut = new InvoiceCreatedEvent($invoice, $model);

        self::assertSame($invoice, $sut->getInvoice());
        self::assertSame($model, $sut->getInvoiceModel());
    }
}
