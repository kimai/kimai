<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\InvoiceTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceTemplate::class)]
class InvoiceTemplateTest extends TestCase
{
    public function testDefaultValues(): void
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
    }

    public function testSetterAndGetter(): void
    {
        $sut = new InvoiceTemplate();

        $sut->setPaymentTerms('foo bar');
        self::assertEquals('foo bar', $sut->getPaymentTerms());

        $sut->setPaymentDetails('iuasdzgf isdfhvlksdjfbnvl ksdfbglisbdf');
        self::assertEquals('iuasdzgf isdfhvlksdjfbnvl ksdfbglisbdf', $sut->getPaymentDetails());

        $sut->setContact('hello world');
        self::assertEquals('hello world', $sut->getContact());

        $sut->setVat(7.31);
        self::assertEquals(7.31, $sut->getVat());

        $sut->setVatId('1234567890');
        self::assertEquals('1234567890', $sut->getVatId());

        $sut->setLanguage('de');
        self::assertEquals('de', $sut->getLanguage());

        $sut->setNumberGenerator('foo');
        self::assertEquals('foo', $sut->getNumberGenerator());

        $sut->setRenderer('bar');
        self::assertEquals('bar', $sut->getRenderer());

        $sut->setCalculator('fooBar');
        self::assertEquals('fooBar', $sut->getCalculator());

        self::assertEquals($sut, clone $sut);
    }

    public function testToString(): void
    {
        $sut = new InvoiceTemplate();

        $sut->setName('a template name');
        self::assertEquals('a template name', $sut->__toString());
        self::assertEquals('a template name', (string) $sut);
    }
}
