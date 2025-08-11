<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Webhook\Attribute;

use App\Entity\Invoice;
use App\Event\InvoiceDeleteEvent;
use App\Webhook\Attribute\AsWebhook;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AsWebhook::class)]
class AsWebhookTestCase extends TestCase
{
    public function testConstruct(): void
    {
        $attribute = new AsWebhook('name', 'description', 'some payload');

        self::assertEquals('name', $attribute->name);
        self::assertEquals('description', $attribute->description);
        self::assertEquals('some payload', $attribute->payload);
    }

    public function testUsage(): void
    {
        $invoice = new Invoice();
        $invoice->setComment('foo bar');

        $usage = new InvoiceDeleteEvent($invoice);

        $ref = new \ReflectionClass($usage);
        $attr = $ref->getAttributes(AsWebhook::class);
        self::assertCount(1, $attr);

        $arguments = $attr[0]->getArguments();
        self::assertEquals('invoice.deleted', $arguments['name']);
        self::assertEquals('Triggered after an invoice was deleted', $arguments['description']);
        self::assertEquals('object.getInvoice()', $arguments['payload']);
    }
}
