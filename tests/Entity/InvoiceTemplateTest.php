<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\InvoiceTemplate;
use App\Entity\InvoiceTemplateMeta;
use App\Entity\Tax;
use App\Entity\TaxType;
use Doctrine\Common\Collections\Collection;
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
        self::assertTrue($sut->isDecimalDuration()); // @phpstan-ignore method.deprecated
        self::assertInstanceOf(Collection::class, $sut->getMetaFields());
        self::assertEquals(0, $sut->getMetaFields()->count());
        self::assertNull($sut->getMetaField('foo'));
    }

    public function testTaxRates(): void
    {
        $sut = new InvoiceTemplate();

        $rates = $sut->getTaxRates();
        self::assertCount(1, $rates);
        self::assertEquals(TaxType::STANDARD, $rates[0]->getType());
        self::assertEquals(0.00, $rates[0]->getRate());
        self::assertEquals('vat', $rates[0]->getName());
        self::assertNull($rates[0]->getNote());

        $tax1 = new Tax(
            TaxType::REVERSE,
            $this->vat ?? 0.00,
            'tax.name.reverse_charge',
            true,
            null
        );
        $tax2 = new Tax(
            TaxType::EXEMPT,
            $this->vat ?? 0.00,
            'tax.name.exempt',
            true,
            null
        );

        $sut->setTaxRates([$tax1, $tax2]);
        $rates = $sut->getTaxRates();

        self::assertCount(2, $rates);

        self::assertEquals(TaxType::REVERSE, $rates[0]->getType());
        self::assertEquals(0.00, $rates[0]->getRate());
        self::assertEquals('tax.name.reverse_charge', $rates[0]->getName());
        self::assertNull($rates[0]->getNote());

        self::assertEquals(TaxType::EXEMPT, $rates[1]->getType());
        self::assertEquals(0.00, $rates[1]->getRate());
        self::assertEquals('tax.name.exempt', $rates[1]->getName());
        self::assertNull($rates[1]->getNote());
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

        $sut->setVatId('1234567890'); // @phpstan-ignore method.deprecated
        self::assertEquals('1234567890', $sut->getVatId());

        $sut->setLanguage('de');
        self::assertEquals('de', $sut->getLanguage());

        $sut->setNumberGenerator('foo');
        self::assertEquals('foo', $sut->getNumberGenerator());

        $sut->setRenderer('bar');
        self::assertEquals('bar', $sut->getRenderer());

        $sut->setCalculator('fooBar');
        self::assertEquals('fooBar', $sut->getCalculator());

        $sut->setCompany('looney toon'); // @phpstan-ignore method.deprecated
        self::assertEquals('looney toon', $sut->getCompany());

        $sut->setAddress('acme street, 1234 looney town, rainbow'); // @phpstan-ignore method.deprecated
        self::assertEquals('acme street, 1234 looney town, rainbow', $sut->getAddress());

        self::assertEquals($sut, clone $sut);

        $customer = new Customer('foo');
        $customer->setVatId('0987654321');
        $customer->setCompany('bar');
        $customer->setAddressLine1('elmstreet');
        $customer->setAddressLine2('2nd floor');
        $customer->setPostCode('4711');
        $customer->setCity('Over the rainbow');
        $sut->setCustomer($customer);

        self::assertEquals('elmstreet
2nd floor
4711 Over the rainbow', $sut->getAddress());
        self::assertEquals('bar', $sut->getCompany());
        self::assertEquals('0987654321', $sut->getVatId());
    }

    public function testToString(): void
    {
        $sut = new InvoiceTemplate();

        $sut->setName('a template name');
        self::assertEquals('a template name', $sut->__toString());
        self::assertEquals('a template name', (string) $sut);
    }

    public function testMetaFields(): void
    {
        $sut = new InvoiceTemplate();
        $meta = new InvoiceTemplateMeta();
        $meta->setName('foo')->setValue('bar2')->setType('test');
        self::assertInstanceOf(InvoiceTemplate::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());
        self::assertEquals('bar2', $result->getValue());

        $meta2 = new InvoiceTemplateMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        self::assertInstanceOf(InvoiceTemplate::class, $sut->setMetaField($meta2));
        self::assertEquals(1, $sut->getMetaFields()->count());
        self::assertCount(0, $sut->getVisibleMetaFields());

        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test2', $result->getType());

        $sut->setMetaField((new InvoiceTemplateMeta())->setName('blub')->setIsVisible(true));
        $sut->setMetaField((new InvoiceTemplateMeta())->setName('blab')->setIsVisible(true));
        self::assertEquals(3, $sut->getMetaFields()->count());
        self::assertCount(2, $sut->getVisibleMetaFields());
    }

    public function testThrowsOnMetaFieldWithoutName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta-field needs to have a name');
        $sut = new InvoiceTemplate();
        $sut->setMetaField(new CustomerMeta());
    }

    public function testThrowsOnMetaFieldsWrongType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta-field needs to be an instanceof InvoiceTemplateMeta');
        $sut = new InvoiceTemplate();
        $meta = new CustomerMeta();
        $meta->setName('foo');
        $sut->setMetaField($meta);
    }
}
