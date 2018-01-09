<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiTest\AppBundle\Repository\Query;

use AppBundle\Entity\User;
use AppBundle\Repository\Query\BaseQuery;
use \PHPUnit\Framework\TestCase;

/**
 * @covers \AppBundle\Repository\Query\BaseQuery
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class BaseQueryTest extends TestCase
{

    public function testQuery()
    {
        $this->assertBaseQuery(new BaseQuery());
    }

    protected function assertBaseQuery(BaseQuery $sut)
    {
        $this->assertResultType($sut);
        $this->assertHiddenEntity($sut);
        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut);
        $this->assertOrder($sut);
    }

    protected function assertResultType(BaseQuery $sut)
    {
        $this->assertEquals(BaseQuery::RESULT_TYPE_PAGER, $sut->getResultType());

        $sut->setResultType('foo-bar');
        $this->assertEquals(BaseQuery::RESULT_TYPE_PAGER, $sut->getResultType());

        $sut->setResultType(BaseQuery::RESULT_TYPE_QUERYBUILDER);
        $this->assertEquals(BaseQuery::RESULT_TYPE_QUERYBUILDER, $sut->getResultType());
    }

    protected function assertHiddenEntity(BaseQuery $sut)
    {
        $this->assertNull($sut->getHiddenEntity());

        $actual = new User();
        $actual->setUsername('foo-bar');

        $sut->setHiddenEntity($actual);
        $this->assertEquals($actual, $sut->getHiddenEntity());
    }

    protected function assertPage(BaseQuery $sut)
    {
        $this->assertEquals(BaseQuery::DEFAULT_PAGE, $sut->getPage());

        $sut->setPage(42);
        $this->assertEquals(42, $sut->getPage());
    }

    protected function assertPageSize(BaseQuery $sut)
    {
        $this->assertEquals(BaseQuery::DEFAULT_PAGESIZE, $sut->getPageSize());

        $sut->setPageSize(100);
        $this->assertEquals(100, $sut->getPageSize());
    }

    protected function assertOrderBy(BaseQuery $sut, $column = 'id')
    {
        $this->assertEquals($column, $sut->getOrderBy());

        $sut->setOrderBy('foo');
        $this->assertEquals('foo', $sut->getOrderBy());
    }

    protected function assertOrder(BaseQuery $sut, $order = BaseQuery::ORDER_ASC)
    {
        $this->assertEquals($order, $sut->getOrder());

        $sut->setOrder('foo');
        $this->assertEquals($order, $sut->getOrder());

        $sut->setOrder(BaseQuery::ORDER_ASC);
        $this->assertEquals(BaseQuery::ORDER_ASC, $sut->getOrder());

        $sut->setOrder(BaseQuery::ORDER_DESC);
        $this->assertEquals(BaseQuery::ORDER_DESC, $sut->getOrder());
    }
}
