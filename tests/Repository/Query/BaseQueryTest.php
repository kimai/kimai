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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @covers \App\Repository\Query\BaseQuery
 */
class BaseQueryTest extends TestCase
{
    public function testQuery()
    {
        $this->assertBaseQuery(new BaseQuery());
        $this->assertResetByFormError(new BaseQuery());
    }

    /**
     * @expectedDeprecation BaseQuery::getResultType() is deprecated and will be removed with 1.6
     * @group legacy
     */
    public function testDeprecations()
    {
        $sut = new BaseQuery();
        $sut->getResultType();
    }

    protected function assertResetByFormError(BaseQuery $sut, $orderBy = 'id', $order = 'ASC')
    {
        $sut->setOrder('ASK');
        $sut->setOrderBy('foo');
        $sut->setPage(99);
        $sut->setPageSize(99);
        $sut->setSearchTerm(new SearchTerm('sdf'));

        $this->resetByFormError($sut, ['order', 'orderBy', 'page', 'pageSize', 'searchTerm']);

        self::assertEquals(1, $sut->getPage());
        self::assertEquals(50, $sut->getPageSize());
        self::assertEquals($order, $sut->getOrder());
        self::assertEquals($orderBy, $sut->getOrderBy());
        self::assertNull($sut->getSearchTerm());
    }

    protected function assertBaseQuery(BaseQuery $sut, $orderBy = 'id')
    {
        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, $orderBy);
        $this->assertOrder($sut);
        $this->assertTeams($sut);
    }

    private function getFormBuilder(string $name)
    {
        return new FormBuilder($name, null, new EventDispatcher(), $this->createMock(FormFactoryInterface::class), []);
    }

    protected function resetByFormError(BaseQuery $sut, array $invalidFields)
    {
        $formBuilder = $this->getFormBuilder('form');
        $formBuilder->setCompound(true);
        $formBuilder->setDataMapper($this->createMock(DataMapperInterface::class));

        $form = $formBuilder->getForm();

        foreach ($invalidFields as $fieldName) {
            $form->add($this->getFormBuilder($fieldName)->getForm());
        }

        $form->submit([]);

        foreach ($invalidFields as $fieldName) {
            $form->get($fieldName)->addError(new FormError('Failed'));
        }

        $formErrors = $form->getErrors(true);

        $sut->resetByFormError($formErrors);
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
