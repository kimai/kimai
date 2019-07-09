<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\VisibilityQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\Query\VisibilityQuery
 */
class VisibilityQueryTest extends TestCase
{
    public function testVisibilityQuery()
    {
        $sut = new VisibilityQuery();

        $this->assertEquals(VisibilityQuery::SHOW_VISIBLE, $sut->getVisibility());

        $sut->setVisibility('foo-bar');
        $this->assertEquals(VisibilityQuery::SHOW_VISIBLE, $sut->getVisibility());

        $sut->setVisibility('2');
        $this->assertEquals(VisibilityQuery::SHOW_HIDDEN, $sut->getVisibility());

        $sut->setVisibility('0'); // keep the value that was previously set
        $this->assertEquals(VisibilityQuery::SHOW_HIDDEN, $sut->getVisibility());

        $sut->setVisibility(VisibilityQuery::SHOW_BOTH);
        $this->assertEquals(VisibilityQuery::SHOW_BOTH, $sut->getVisibility());

        $sut->setVisibility(VisibilityQuery::SHOW_HIDDEN);
        $this->assertEquals(VisibilityQuery::SHOW_HIDDEN, $sut->getVisibility());

        $sut->setVisibility(VisibilityQuery::SHOW_VISIBLE);
        $this->assertEquals(VisibilityQuery::SHOW_VISIBLE, $sut->getVisibility());
    }
}
