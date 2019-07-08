<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\User;
use App\Repository\Query\BaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\Query\BaseQuery
 */
class BaseQueryTest extends TestCase
{
    public function testQuery()
    {
        $this->assertBaseQuery(new BaseQuery());
    }

    protected function assertBaseQuery(BaseQuery $sut, $orderBy = 'id')
    {
        $this->assertResultType($sut);
        $this->assertHiddenEntity($sut);
        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, $orderBy);
        $this->assertOrder($sut);
    }

    protected function assertResultType(BaseQuery $sut)
    {
        $this->assertEquals(BaseQuery::RESULT_TYPE_PAGER, $sut->getResultType());

        $sut->setResultType(BaseQuery::RESULT_TYPE_QUERYBUILDER);
        $this->assertEquals(BaseQuery::RESULT_TYPE_QUERYBUILDER, $sut->getResultType());

        $sut->setResultType(BaseQuery::RESULT_TYPE_OBJECTS);
        $this->assertEquals(BaseQuery::RESULT_TYPE_OBJECTS, $sut->getResultType());

        try {
            $sut->setResultType('foo-bar');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
            $this->assertEquals('Unsupported query result type', $exception->getMessage());
        }
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
