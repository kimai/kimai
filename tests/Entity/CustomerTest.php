<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Customer;
use App\Entity\Project;

/**
 * @covers \App\Entity\Customer
 */
class CustomerTest extends AbstractEntityTest
{
    public function testDefaultValues()
    {
        $sut = new Customer();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getNumber());
        $this->assertNull($sut->getComment());
        // projects
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
        $this->assertNull($sut->getMail());
        $this->assertNull($sut->getHomepage());
        $this->assertNull($sut->getTimezone());

        $this->assertNull($sut->getFixedRate());
        $this->assertNull($sut->getHourlyRate());
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

        $projects = [(new Project())->setName('Test')];
        $this->assertInstanceOf(Customer::class, $sut->setProjects($projects));
        $this->assertSame($projects, $sut->getProjects());

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

        $this->assertInstanceOf(Customer::class, $sut->setMail('test@example.com'));
        $this->assertEquals('test@example.com', $sut->getMail());

        $this->assertInstanceOf(Customer::class, $sut->setHomepage('https://www.example.com'));
        $this->assertEquals('https://www.example.com', $sut->getHomepage());

        $this->assertInstanceOf(Customer::class, $sut->setFixedRate(13.47));
        $this->assertEquals(13.47, $sut->getFixedRate());
        $this->assertInstanceOf(Customer::class, $sut->setHourlyRate(99));
        $this->assertEquals(99, $sut->getHourlyRate());
    }
}
