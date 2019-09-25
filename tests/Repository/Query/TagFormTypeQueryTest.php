<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\User;
use App\Repository\Query\TagFormTypeQuery;

/**
 * @covers \App\Repository\Query\TagFormTypeQuery
 */
class TagFormTypeQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new TagFormTypeQuery();

        $user = new User();
        self::assertNull($sut->getUser());
        self::assertInstanceOf(TagFormTypeQuery::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());
    }
}
