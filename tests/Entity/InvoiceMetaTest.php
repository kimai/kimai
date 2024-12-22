<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Customer;
use App\Entity\EntityWithMetaFields;
use App\Entity\Invoice;
use App\Entity\InvoiceMeta;
use App\Entity\MetaTableTypeInterface;

/**
 * @covers \App\Entity\InvoiceMeta
 */
class InvoiceMetaTest extends AbstractMetaEntityTestCase
{
    protected function getEntity(): EntityWithMetaFields
    {
        return new Invoice();
    }

    protected function getMetaEntity(): MetaTableTypeInterface
    {
        return new InvoiceMeta();
    }

    public function testSetEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instanceof Invoice, received "App\Entity\Customer"');

        $sut = new InvoiceMeta();
        $sut->setEntity(new Customer('foo'));
    }
}
