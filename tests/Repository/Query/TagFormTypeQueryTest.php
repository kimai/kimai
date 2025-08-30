<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\BaseFormTypeQuery;
use App\Repository\Query\TagFormTypeQuery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TagFormTypeQuery::class)]
#[CoversClass(BaseFormTypeQuery::class)]
class TagFormTypeQueryTest extends AbstractBaseFormTypeQueryTestCase
{
    public function testQuery(): void
    {
        $sut = new TagFormTypeQuery();

        $this->assertBaseQuery($sut);
    }
}
