<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\InvoiceDocumentsEvent;
use App\Model\InvoiceDocument;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\InvoiceDocumentsEvent
 */
class InvoiceDocumentsEventTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new InvoiceDocumentsEvent([]);

        self::assertEquals([], $sut->getInvoiceDocuments());
        self::assertEquals(99, $sut->getMaximumAllowedDocuments());

        $sut->setMaximumAllowedDocuments(10);
        self::assertEquals(10, $sut->getMaximumAllowedDocuments());

        $file = new \SplFileInfo(__FILE__);
        $document = new InvoiceDocument($file);

        $sut->setInvoiceDocuments([$document]);
        self::assertEquals([$document], $sut->getInvoiceDocuments());

        $sut->addInvoiceDocuments(new InvoiceDocument($file));
        self::assertCount(2, $sut->getInvoiceDocuments());
    }
}
