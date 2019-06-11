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
    protected function assertIsFluent($actual)
    {
        $this->assertInstanceOf(InvoiceTemplate::class, $actual);
    }

    protected function getEntity()
    {
        $entity = new InvoiceTemplate();

        return $entity;
    }

    public function testSetGetPaymentTerms()
    {
        $sut = $this->getEntity();

        $this->assertNull($sut->getPaymentTerms());
        $this->assertIsFluent($sut->setPaymentTerms(null));
        $this->assertIsFluent($sut->setPaymentTerms(''));
        $this->assertIsFluent($sut->setPaymentTerms('foo bar'));
        $this->assertEquals('foo bar', $sut->getPaymentTerms());
    }

    public function testToString()
    {
        $sut = $this->getEntity();

        $this->assertNull($sut->__toString());
        $this->assertIsFluent($sut->setName('a template name'));
        $this->assertEquals('a template name', $sut->__toString());
        $this->assertEquals('a template name', (string) $sut);
    }
}
