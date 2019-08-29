<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Team;
use App\Repository\Query\BaseQuery;
use App\Utils\SearchTerm;
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
        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, $orderBy);
        $this->assertOrder($sut);
        $this->assertTeams($sut);
    }

    protected function assertResultType(BaseQuery $sut)
    {
        self::assertEquals(BaseQuery::RESULT_TYPE_PAGER, $sut->getResultType());

        $sut->setResultType(BaseQuery::RESULT_TYPE_QUERYBUILDER);
        self::assertEquals(BaseQuery::RESULT_TYPE_QUERYBUILDER, $sut->getResultType());

        $sut->setResultType(BaseQuery::RESULT_TYPE_OBJECTS);
        self::assertEquals(BaseQuery::RESULT_TYPE_OBJECTS, $sut->getResultType());

        try {
            $sut->setResultType('foo-bar');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
            self::assertEquals('Unsupported query result type', $exception->getMessage());
        }
    }

    protected function assertTeams(BaseQuery $sut)
    {
        self::assertEmpty($sut->getTeams());

        self::assertInstanceOf(BaseQuery::class, $sut->addTeam(new Team()));
        self::assertEquals(1, count($sut->getTeams()));
    }

    protected function assertPage(BaseQuery $sut)
    {
        self::assertEquals(BaseQuery::DEFAULT_PAGE, $sut->getPage());

        $sut->setPage(42);
        self::assertEquals(42, $sut->getPage());
    }

    protected function assertPageSize(BaseQuery $sut)
    {
        self::assertEquals(BaseQuery::DEFAULT_PAGESIZE, $sut->getPageSize());

        $sut->setPageSize(100);
        self::assertEquals(100, $sut->getPageSize());
    }

    protected function assertOrderBy(BaseQuery $sut, $column = 'id')
    {
        self::assertEquals($column, $sut->getOrderBy());

        $sut->setOrderBy('foo');
        self::assertEquals('foo', $sut->getOrderBy());
    }

    protected function assertOrder(BaseQuery $sut, $order = BaseQuery::ORDER_ASC)
    {
        self::assertEquals($order, $sut->getOrder());

        $sut->setOrder('foo');
        self::assertEquals($order, $sut->getOrder());

        $sut->setOrder(BaseQuery::ORDER_ASC);
        self::assertEquals(BaseQuery::ORDER_ASC, $sut->getOrder());

        $sut->setOrder(BaseQuery::ORDER_DESC);
        self::assertEquals(BaseQuery::ORDER_DESC, $sut->getOrder());
    }

    protected function assertSearchTerm(BaseQuery $sut)
    {
        self::assertNull($sut->getSearchTerm());

        $sut->setSearchTerm(null);
        self::assertNull($sut->getSearchTerm());

        $term = new SearchTerm('foo bar');
        $sut->setSearchTerm($term);
        
        self::assertNotNull($sut->getSearchTerm());
        self::assertEquals('foo bar', $term->getOriginalSearch());
        self::assertSame($term, $sut->getSearchTerm());
    }
}
