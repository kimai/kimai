<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\UserFormTypeQuery;

/**
 * @covers \App\Repository\Query\UserFormTypeQuery
 * @covers \App\Repository\Query\BaseFormTypeQuery
 */
class UserFormTypeQueryTest extends BaseFormTypeQueryTest
{
    public function testQuery()
    {
        $sut = new UserFormTypeQuery();

        $this->assertBaseQuery($sut);
    }
}
