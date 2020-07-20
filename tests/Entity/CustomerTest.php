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
use App\Entity\Team;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Customer
 */
class CustomerTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new Customer();
        self::assertNull($sut->getId());
        self::assertNull($sut->getName());
        self::assertNull($sut->getNumber());
        self::assertNull($sut->getComment());
        self::assertTrue($sut->isVisible());

        self::assertNull($sut->getCompany());
        self::assertNull($sut->getVatId());
        self::assertNull($sut->getContact());
        self::assertNull($sut->getAddress());
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
        self::assertEquals(0.0, $sut->getBudget());
        self::assertEquals(0, $sut->getTimeBudget());
        self::assertInstanceOf(Collection::class, $sut->getMetaFields());
        self::assertEquals(0, $sut->getMetaFields()->count());
        self::assertNull($sut->getMetaField('foo'));
        self::assertInstanceOf(Collection::class, $sut->getTeams());
        self::assertEquals(0, $sut->getTeams()->count());
    }

    public function testSetterAndGetter()
    {
        $sut = new Customer();
        self::assertInstanceOf(Customer::class, $sut->setName('foo-bar'));
        self::assertEquals('foo-bar', $sut->getName());
        self::assertEquals('foo-bar', (string) $sut);

        self::assertInstanceOf(Customer::class, $sut->setVisible(false));
        self::assertFalse($sut->isVisible());

        self::assertInstanceOf(Customer::class, $sut->setComment('hello world'));
        self::assertEquals('hello world', $sut->getComment());

        self::assertInstanceOf(Customer::class, $sut->setColor('#fffccc'));
        self::assertEquals('#fffccc', $sut->getColor());

        self::assertInstanceOf(Customer::class, $sut->setCompany('test company'));
        self::assertEquals('test company', $sut->getCompany());

        self::assertInstanceOf(Customer::class, $sut->setContact('test contact'));
        self::assertEquals('test contact', $sut->getContact());

        self::assertInstanceOf(Customer::class, $sut->setPhone('0123456789'));
        self::assertEquals('0123456789', $sut->getPhone());

        self::assertInstanceOf(Customer::class, $sut->setFax('asdfghjkl'));
        self::assertEquals('asdfghjkl', $sut->getFax());

        self::assertInstanceOf(Customer::class, $sut->setMobile('76576534'));
        self::assertEquals('76576534', $sut->getMobile());

        self::assertInstanceOf(Customer::class, $sut->setEmail('test@example.com'));
        self::assertEquals('test@example.com', $sut->getEmail());

        self::assertInstanceOf(Customer::class, $sut->setHomepage('https://www.example.com'));
        self::assertEquals('https://www.example.com', $sut->getHomepage());

        self::assertInstanceOf(Customer::class, $sut->setBudget(12345.67));
        self::assertEquals(12345.67, $sut->getBudget());

        self::assertInstanceOf(Customer::class, $sut->setTimeBudget(937321));
        self::assertEquals(937321, $sut->getTimeBudget());

        self::assertInstanceOf(Customer::class, $sut->setVatId('ID 1234567890'));
        self::assertEquals('ID 1234567890', $sut->getVatId());
    }

    public function testMetaFields()
    {
        $sut = new Customer();
        $meta = new CustomerMeta();
        $meta->setName('foo')->setValue('bar')->setType('test');
        self::assertInstanceOf(Customer::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());

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

    public function testTeams()
    {
        $sut = new Customer();
        $team = new Team();
        self::assertEmpty($sut->getTeams());
        self::assertEmpty($team->getCustomers());

        $sut->addTeam($team);
        self::assertCount(1, $sut->getTeams());
        self::assertCount(1, $team->getCustomers());
        self::assertSame($team, $sut->getTeams()[0]);
        self::assertSame($sut, $team->getCustomers()[0]);

        $sut->removeTeam(new Team());
        $sut->removeTeam($team);
        self::assertCount(0, $sut->getTeams());
        self::assertCount(0, $team->getCustomers());
    }

    public function testExportAnnotations()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $columns = $sut->extract(Customer::class);

        self::assertIsArray($columns);

        $expected = [
            ['label.id', 'integer'],
            ['label.name', 'string'],
            ['label.company', 'string'],
            ['label.number', 'string'],
            ['label.vat_id', 'string'],
            ['label.address', 'string'],
            ['label.contact', 'string'],
            ['label.email', 'string'],
            ['label.phone', 'string'],
            ['label.mobile', 'string'],
            ['label.fax', 'string'],
            ['label.homepage', 'string'],
            ['label.country', 'string'],
            ['label.currency', 'string'],
            ['label.timezone', 'string'],
            ['label.color', 'string'],
            ['label.visible', 'boolean'],
            ['label.comment', 'string'],
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
}
