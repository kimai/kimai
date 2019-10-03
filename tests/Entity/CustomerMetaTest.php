<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;

/**
 * @covers \App\Entity\CustomerMeta
 */
class CustomerMetaTest extends AbstractMetaEntityTest
{
    protected function getEntity(): EntityWithMetaFields
    {
        return new Customer();
    }

    protected function getMetaEntity(): MetaTableTypeInterface
    {
        return new CustomerMeta();
    }

    public function testSetEntityThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instanceof Customer, received "App\Entity\Activity"');

        $sut = new CustomerMeta();
        $sut->setEntity(new Activity());
    }
}
