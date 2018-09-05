<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Customer;

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
        //
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

        $this->assertInstanceOf(Customer::class, $sut->setFixedRate(13.47));
        $this->assertEquals(13.47, $sut->getFixedRate());
        $this->assertInstanceOf(Customer::class, $sut->setHourlyRate(99));
        $this->assertEquals(99, $sut->getHourlyRate());
    }
}
