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
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getNumber());
        $this->assertNull($sut->getComment());
        $this->assertTrue($sut->getVisible());

        $this->assertNull($sut->getCompany());
        $this->assertNull($sut->getContact());
        $this->assertNull($sut->getAddress());
        $this->assertNull($sut->getCountry());
        $this->assertEquals('EUR', $sut->getCurrency());
        $this->assertEquals('EUR', Customer::DEFAULT_CURRENCY);
        $this->assertNull($sut->getPhone());
        $this->assertNull($sut->getFax());
        $this->assertNull($sut->getMobile());
        $this->assertNull($sut->getEmail());
        $this->assertNull($sut->getHomepage());
        $this->assertNull($sut->getTimezone());

        $this->assertNull($sut->getFixedRate());
        $this->assertNull($sut->getHourlyRate());
        $this->assertNull($sut->getColor());
        $this->assertEquals(0.0, $sut->getBudget());
        $this->assertEquals(0, $sut->getTimeBudget());
        $this->assertInstanceOf(Collection::class, $sut->getMetaFields());
        $this->assertEquals(0, $sut->getMetaFields()->count());
        $this->assertNull($sut->getMetaField('foo'));
        $this->assertInstanceOf(Collection::class, $sut->getTeams());
        $this->assertEquals(0, $sut->getTeams()->count());
    }

    public function testSetterAndGetter()
    {
        $sut = new Customer();
        $this->assertInstanceOf(Customer::class, $sut->setName('foo-bar'));
        $this->assertEquals('foo-bar', $sut->getName());
        $this->assertEquals('foo-bar', (string) $sut);

        $this->assertInstanceOf(Customer::class, $sut->setVisible(false));
        $this->assertFalse($sut->getVisible());

        $this->assertInstanceOf(Customer::class, $sut->setComment('hello world'));
        $this->assertEquals('hello world', $sut->getComment());

        $this->assertInstanceOf(Customer::class, $sut->setColor('#fffccc'));
        $this->assertEquals('#fffccc', $sut->getColor());

        $this->assertInstanceOf(Customer::class, $sut->setCompany('test company'));
        $this->assertEquals('test company', $sut->getCompany());

        $this->assertInstanceOf(Customer::class, $sut->setContact('test contact'));
        $this->assertEquals('test contact', $sut->getContact());

        $this->assertInstanceOf(Customer::class, $sut->setPhone('0123456789'));
        $this->assertEquals('0123456789', $sut->getPhone());

        $this->assertInstanceOf(Customer::class, $sut->setFax('asdfghjkl'));
        $this->assertEquals('asdfghjkl', $sut->getFax());

        $this->assertInstanceOf(Customer::class, $sut->setMobile('76576534'));
        $this->assertEquals('76576534', $sut->getMobile());

        $this->assertInstanceOf(Customer::class, $sut->setEmail('test@example.com'));
        $this->assertEquals('test@example.com', $sut->getEmail());

        $this->assertInstanceOf(Customer::class, $sut->setHomepage('https://www.example.com'));
        $this->assertEquals('https://www.example.com', $sut->getHomepage());

        $this->assertInstanceOf(Customer::class, $sut->setFixedRate(13.47));
        $this->assertEquals(13.47, $sut->getFixedRate());

        $this->assertInstanceOf(Customer::class, $sut->setHourlyRate(99));
        $this->assertEquals(99, $sut->getHourlyRate());

        $this->assertInstanceOf(Customer::class, $sut->setBudget(12345.67));
        $this->assertEquals(12345.67, $sut->getBudget());

        $this->assertInstanceOf(Customer::class, $sut->setTimeBudget(937321));
        $this->assertEquals(937321, $sut->getTimeBudget());
    }

    public function testMetaFields()
    {
        $sut = new Customer();
        $meta = new CustomerMeta();
        $meta->setName('foo')->setValue('bar')->setType('test');
        $this->assertInstanceOf(Customer::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());

        $meta2 = new CustomerMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        $this->assertInstanceOf(Customer::class, $sut->setMetaField($meta2));
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
}
