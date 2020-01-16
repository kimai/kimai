<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\CommentInterface;
use App\Entity\Customer;
use App\Entity\CustomerComment;

/**
 * @covers \App\Entity\CustomerComment
 * @covers \App\Entity\CommentTableTypeTrait
 */
class CustomerCommentTest extends AbstractCommentEntityTest
{
    protected function getEntity(): CommentInterface
    {
        return new CustomerComment();
    }

    public function testEntitySpecificMethods()
    {
        $sut = new CustomerComment();
        self::assertNull($sut->getCustomer());

        $customer = new Customer();
        self::assertInstanceOf(CustomerComment::class, $sut->setCustomer($customer));
        self::assertSame($customer, $sut->getCustomer());
    }
}
