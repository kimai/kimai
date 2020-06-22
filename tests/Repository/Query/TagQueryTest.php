<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\TagQuery;

/**
 * @covers \App\Repository\Query\TagQuery
 */
class TagQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new TagQuery();

        $this->assertBaseQuery($sut, 'name');
        $this->assertInstanceOf(TagQuery::class, $sut);

        $this->assertResetByFormError(new TagQuery(), 'name');
    }
}
