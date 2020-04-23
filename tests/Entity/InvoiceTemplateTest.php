<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\InvoiceTemplate;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\InvoiceTemplate
 */
class InvoiceTemplateTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new InvoiceTemplate();

        self::assertNull($sut->getVatId());
        self::assertNull($sut->getPaymentDetails());
        self::assertNull($sut->getPaymentTerms());
        self::assertNull($sut->getContact());
        self::assertEquals(0.00, $sut->getVat());
        self::assertNull($sut->getId());
        self::assertNull($sut->getName());
        self::assertNull($sut->getAddress());
        self::assertNull($sut->getTitle());
        self::assertNull($sut->getCompany());
        self::assertEquals('default', $sut->getCalculator());
        self::assertEquals('default', $sut->getNumberGenerator());
        self::assertEquals('default', $sut->getRenderer());
        self::assertEquals(30, $sut->getDueDays());
        self::assertFalse($sut->isDecimalDuration());
        self::assertNull($sut->getLanguage());
    }

    public function testSetNullForOptionalValues()
    {
        $sut = new InvoiceTemplate();

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setPaymentDetails(null));
        self::assertInstanceOf(InvoiceTemplate::class, $sut->setVatId(null));
        self::assertInstanceOf(InvoiceTemplate::class, $sut->setContact(null));
        self::assertInstanceOf(InvoiceTemplate::class, $sut->setAddress(null));
        self::assertInstanceOf(InvoiceTemplate::class, $sut->setPaymentTerms(null));
        self::assertInstanceOf(InvoiceTemplate::class, $sut->setLanguage(null));
    }

    public function testSetterAndGetter()
    {
        $sut = new InvoiceTemplate();

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setPaymentTerms('foo bar'));
        self::assertEquals('foo bar', $sut->getPaymentTerms());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setPaymentDetails('iuasdzgf isdfhvlksdjfbnvl ksdfbglisbdf'));
        self::assertEquals('iuasdzgf isdfhvlksdjfbnvl ksdfbglisbdf', $sut->getPaymentDetails());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setContact('hello world'));
        self::assertEquals('hello world', $sut->getContact());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setVat(7.31));
        self::assertEquals(7.31, $sut->getVat());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setVatId('1234567890'));
        self::assertEquals('1234567890', $sut->getVatId());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setDecimalDuration(true));
        self::assertTrue($sut->isDecimalDuration());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setLanguage('de'));
        self::assertEquals('de', $sut->getLanguage());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setNumberGenerator('foo'));
        self::assertEquals('foo', $sut->getNumberGenerator());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setRenderer('bar'));
        self::assertEquals('bar', $sut->getRenderer());

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setCalculator('fooBar'));
        self::assertEquals('fooBar', $sut->getCalculator());

        self::assertEquals($sut, clone $sut);
    }

    public function testToString()
    {
        $sut = new InvoiceTemplate();

        self::assertInstanceOf(InvoiceTemplate::class, $sut->setName('a template name'));
        self::assertEquals('a template name', $sut->__toString());
        self::assertEquals('a template name', (string) $sut);
    }
}
