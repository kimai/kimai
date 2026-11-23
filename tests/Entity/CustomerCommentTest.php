<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\CommentTableTypeTrait;
use App\Entity\Customer;
use App\Entity\CustomerComment;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CommentTableTypeTrait::class)]
#[CoversClass(CustomerComment::class)]
class CustomerCommentTest extends AbstractCommentEntityTestCase
{
    protected function getEntity(): CustomerComment
    {
        return new CustomerComment(new Customer('foo'));
    }
}
