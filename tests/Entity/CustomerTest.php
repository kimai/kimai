<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Constants;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\InvoiceTemplate;
use App\Entity\Team;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Customer::class)]
class CustomerTest extends AbstractEntityTestCase
{
    public function testDefaultValues(): void
    {
        $sut = new Customer('foo');
        self::assertNull($sut->getId());
        self::assertNull($sut->getNumber());
        self::assertNull($sut->getComment());
        self::assertTrue($sut->isVisible());
        self::assertTrue($sut->isBillable());

        self::assertNull($sut->getCompany());
        self::assertNull($sut->getVatId());
        self::assertNull($sut->getContact());
        self::assertNull($sut->getAddress());
        self::assertNull($sut->getAddressLine1());
        self::assertNull($sut->getAddressLine2());
        self::assertNull($sut->getAddressLine3());
        self::assertNull($sut->getFormattedAddress());
        self::assertNull($sut->getCity());
        self::assertNull($sut->getPostCode());
        self::assertNull($sut->getBuyerReference());
        self::assertNull($sut->getCountry());
        self::assertEquals('EUR', $sut->getCurrency());
        self::assertEquals('EUR', Customer::DEFAULT_CURRENCY);
        self::assertNull($sut->getPhone());
        self::assertNull($sut->getFax());
        self::assertNull($sut->getMobile());
        self::assertNull($sut->getEmail());
        self::assertNull($sut->getHomepage());
        self::assertNull($sut->getTimezone());

        self::assertNull($sut->getColor());
        self::assertEquals('#e135f4', $sut->getColorSafe());
        self::assertFalse($sut->hasColor());
        self::assertInstanceOf(Collection::class, $sut->getMetaFields());
        self::assertEquals(0, $sut->getMetaFields()->count());
        self::assertNull($sut->getMetaField('foo'));
        self::assertInstanceOf(Collection::class, $sut->getTeams());
        self::assertEquals(0, $sut->getTeams()->count());
        self::assertTrue($sut->isNew());
        self::assertNull($sut->getInvoiceText());
        self::assertNull($sut->getInvoiceTemplate());
    }

    public function testInvoiceText(): void
    {
        $sut = new Customer('foo');
        self::assertNull($sut->getInvoiceText());
        $sut->setInvoiceText('Some fancy long text to explain that tax should be handled by the receiving party');
        self::assertEquals('Some fancy long text to explain that tax should be handled by the receiving party', $sut->getInvoiceText());
    }

    public function testInvoiceTemplate(): void
    {
        $tpl = new InvoiceTemplate();
        $sut = new Customer('foo');
        self::assertNull($sut->getInvoiceTemplate());
        $sut->setInvoiceTemplate($tpl);
        self::assertSame($tpl, $sut->getInvoiceTemplate());
    }

    public function testBudgets(): void
    {
        $this->assertBudget(new Customer('foo'));
    }

    public function testSetterAndGetter(): void
    {
        $sut = new Customer('foo-bar');
        self::assertEquals('foo-bar', $sut->getName());
        self::assertEquals('foo-bar', (string) $sut);

        $sut->setVisible(false);
        self::assertFalse($sut->isVisible());

        $sut->setVisible(false);
        self::assertFalse($sut->isVisible());
        $sut->setVisible(true);
        self::assertTrue($sut->isVisible());

        $sut->setComment('hello world');
        self::assertEquals('hello world', $sut->getComment());

        self::assertFalse($sut->hasColor());
        $sut->setColor('#fffccc');
        self::assertEquals('#fffccc', $sut->getColor());
        self::assertTrue($sut->hasColor());

        $sut->setColor(Constants::DEFAULT_COLOR);
        self::assertNull($sut->getColor());
        self::assertFalse($sut->hasColor());

        $sut->setCompany('test company');
        self::assertEquals('test company', $sut->getCompany());

        $sut->setContact('test contact');
        self::assertEquals('test contact', $sut->getContact());

        $sut->setPhone('0123456789');
        self::assertEquals('0123456789', $sut->getPhone());

        $sut->setFax('asdfghjkl');
        self::assertEquals('asdfghjkl', $sut->getFax());

        $sut->setMobile('76576534');
        self::assertEquals('76576534', $sut->getMobile());

        $sut->setEmail('test@example.com');
        self::assertEquals('test@example.com', $sut->getEmail());

        $sut->setHomepage('https://www.example.com');
        self::assertEquals('https://www.example.com', $sut->getHomepage());

        $sut->setVatId('ID 1234567890');
        self::assertEquals('ID 1234567890', $sut->getVatId());

        $sut->setCountry(null);
        self::assertNull($sut->getCountry());

        $sut->setCurrency('USD');
        self::assertEquals('USD', $sut->getCurrency());

        $sut->setCurrency(null);
        self::assertNull($sut->getCurrency());

        $sut->setBuyerReference('BR-876876876876');
        self::assertEquals('BR-876876876876', $sut->getBuyerReference());

        $sut->setBuyerReference(null);
        self::assertNull($sut->getBuyerReference());

        $sut->setAddressLine1('address line 1');
        $sut->setAddressLine2('address line 2');
        $sut->setAddressLine3('address line 3');
        $sut->setCity('looney toon');
        $sut->setPostcode('zip 12345');
        $sut->setAddress('foo bar
sdfsadf
sdfarwt34
786876 uitiutziuz');

        self::assertEquals('address line 1', $sut->getAddressLine1());
        self::assertEquals('address line 2', $sut->getAddressLine2());
        self::assertEquals('address line 3', $sut->getAddressLine3());
        self::assertEquals('looney toon', $sut->getCity());
        self::assertEquals('zip 12345', $sut->getPostCode());
        self::assertEquals('foo bar
sdfsadf
sdfarwt34
786876 uitiutziuz', $sut->getAddress());
        self::assertEquals('address line 1
address line 2
address line 3
zip 12345 looney toon', $sut->getFormattedAddress());

        $sut->setAddressLine1(null);
        $sut->setAddressLine2(null);
        $sut->setAddressLine3(null);
        $sut->setCity(null);
        $sut->setPostcode(null);
        $sut->setAddress(null);

        self::assertNull($sut->getAddress());
        self::assertNull($sut->getAddressLine1());
        self::assertNull($sut->getAddressLine2());
        self::assertNull($sut->getAddressLine3());
        self::assertNull($sut->getFormattedAddress());
        self::assertNull($sut->getCity());
        self::assertNull($sut->getPostCode());
    }

    public function testMetaFields(): void
    {
        $sut = new Customer('foo');
        $meta = new CustomerMeta();
        $meta->setName('foo')->setValue('bar2')->setType('test');
        self::assertInstanceOf(Customer::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());
        self::assertEquals('bar2', $result->getValue());

        $meta2 = new CustomerMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        self::assertInstanceOf(Customer::class, $sut->setMetaField($meta2));
        self::assertEquals(1, $sut->getMetaFields()->count());
        self::assertCount(0, $sut->getVisibleMetaFields());

        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test2', $result->getType());

        $sut->setMetaField((new CustomerMeta())->setName('blub')->setIsVisible(true));
        $sut->setMetaField((new CustomerMeta())->setName('blab')->setIsVisible(true));
        self::assertEquals(3, $sut->getMetaFields()->count());
        self::assertCount(2, $sut->getVisibleMetaFields());
    }

    public function testTeams(): void
    {
        $sut = new Customer('foo');
        $team = new Team('foo');
        self::assertEmpty($sut->getTeams());
        self::assertEmpty($team->getCustomers());

        $sut->addTeam($team);
        self::assertCount(1, $sut->getTeams());
        self::assertCount(1, $team->getCustomers());
        self::assertSame($team, $sut->getTeams()[0]);
        self::assertSame($sut, $team->getCustomers()[0]);

        // test remove unknown team doesn't do anything
        $sut->removeTeam(new Team('foo'));
        self::assertCount(1, $sut->getTeams());
        self::assertCount(1, $team->getCustomers());

        $sut->removeTeam(new Team('foo'));
        $sut->removeTeam($team);
        self::assertCount(0, $sut->getTeams());
        self::assertCount(0, $team->getCustomers());
    }

    public function testExportAnnotations(): void
    {
        $sut = new AnnotationExtractor();

        $columns = $sut->extract(Customer::class);

        self::assertIsArray($columns);

        $expected = [
            ['id', 'integer'],
            ['name', 'string'],
            ['company', 'string'],
            ['number', 'string'],
            ['vat_id', 'string'],
            ['address', 'string'],
            ['contact', 'string'],
            ['email', 'string'],
            ['phone', 'string'],
            ['mobile', 'string'],
            ['fax', 'string'],
            ['homepage', 'string'],
            ['address_line1', 'string'],
            ['address_line2', 'string'],
            ['address_line3', 'string'],
            ['postcode', 'string'],
            ['city', 'string'],
            ['country', 'string'],
            ['currency', 'string'],
            ['timezone', 'string'],
            ['budget', 'float'],
            ['timeBudget', 'duration'],
            ['budgetType', 'string'],
            ['color', 'string'],
            ['visible', 'boolean'],
            ['comment', 'string'],
            ['billable', 'boolean'],
            ['buyerReference', 'string'],
        ];

        self::assertCount(\count($expected), $columns);

        foreach ($columns as $column) {
            self::assertInstanceOf(ColumnDefinition::class, $column);
        }

        $i = 0;

        foreach ($expected as $item) {
            $column = $columns[$i++];
            self::assertEquals($item[0], $column->getLabel());
            self::assertEquals($item[1], $column->getType());
        }
    }

    public function testClone(): void
    {
        $sut = new Customer('mycustomer');

        $this->assertCloneResetsId($sut);

        $sut->setVatId('DE-0123456789');
        $sut->setTimeBudget(123456);
        $sut->setBudget(1234.56);

        $team = new Team('foo');
        $sut->addTeam($team);

        $meta = new CustomerMeta();
        $meta->setName('blabla');
        $meta->setValue('1234567890');
        $meta->setIsVisible(false);
        $meta->setIsRequired(true);
        $sut->setMetaField($meta);

        $clone = clone $sut;

        foreach ($sut->getMetaFields() as $metaField) {
            $cloneMeta = $clone->getMetaField($metaField->getName());
            self::assertEquals($cloneMeta->getValue(), $metaField->getValue());
        }
        self::assertEquals($clone->getBudget(), $sut->getBudget());
        self::assertEquals($clone->getTimeBudget(), $sut->getTimeBudget());
        self::assertEquals($clone->getColor(), $sut->getColor());
        self::assertEquals('DE-0123456789', $clone->getVatId());
        self::assertEquals('mycustomer', $clone->getName());
    }
}
