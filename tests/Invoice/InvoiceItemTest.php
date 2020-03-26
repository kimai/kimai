<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Invoice\InvoiceItem;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\InvoiceItem
 */
class InvoiceItemTest extends TestCase
{
    public function testEmptyObject()
    {
        $sut = new InvoiceItem();

        self::assertFalse($sut->isFixedRate());
        self::assertNull($sut->getHourlyRate());
        self::assertNull($sut->getFixedRate());
        self::assertNull($sut->getEnd());
        self::assertEquals(0.00, $sut->getRate());
        self::assertEquals(0.00, $sut->getInternalRate());
        self::assertNull($sut->getProject());
        self::assertIsArray($sut->getAdditionalFields());
        self::assertEmpty($sut->getAdditionalFields());
        self::assertEquals(0, $sut->getAmount());
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getActivity());
        self::assertNull($sut->getUser());
        self::assertNull($sut->getDescription());
        self::assertEquals(0, $sut->getDuration());
        self::assertNull($sut->getCategory());
        self::assertNull($sut->getType());
    }
}
