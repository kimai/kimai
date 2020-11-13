<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TimesheetQuery;
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
     * @expectedDeprecation BaseQuery::getResultType() is deprecated and will be removed with 2.0
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
        self::assertEquals(1, \count($sut->getTeams()));

        $sut->setTeams(null);
        self::assertEmpty($sut->getTeams());
        $sut->setTeams([]);
        self::assertEmpty($sut->getTeams());

        $team = new Team();
        self::assertInstanceOf(BaseQuery::class, $sut->setTeams([$team]));
        self::assertEquals(1, \count($sut->getTeams()));
        self::assertSame($team, $sut->getTeams()[0]);
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

    protected function assertActivity(TimesheetQuery $sut)
    {
        $this->assertNull($sut->getActivity());
        $this->assertEquals([], $sut->getActivities());
        $this->assertFalse($sut->hasActivities());

        $expected = new Activity();
        $expected->setName('foo-bar');

        $sut->setActivity($expected);
        $this->assertEquals($expected, $sut->getActivity());

        $sut->setActivities([]);
        $this->assertEquals([], $sut->getActivities());

        $sut->addActivity($expected);
        $this->assertEquals([$expected], $sut->getActivities());
        $this->assertTrue($sut->hasActivities());

        $expected2 = new Activity();
        $expected2->setName('foo-bar2');

        $sut->addActivity($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getActivities());

        $sut->setActivity(null);
        $this->assertNull($sut->getActivity());
        $this->assertFalse($sut->hasActivities());

        // make sure int is allowed as well
        $sut->setActivities([99]);
        $this->assertEquals(99, $sut->getActivity());
        $this->assertEquals([99], $sut->getActivities());
    }

    protected function assertCustomer(ProjectQuery $sut)
    {
        $this->assertNull($sut->getCustomer());
        $this->assertEquals([], $sut->getCustomers());
        $this->assertFalse($sut->hasCustomers());

        $expected = new Customer();
        $expected->setName('foo-bar');

        $sut->setCustomer($expected);
        $this->assertEquals($expected, $sut->getCustomer());

        $sut->setCustomers([]);
        $this->assertEquals([], $sut->getCustomers());

        $sut->addCustomer($expected);
        $this->assertEquals([$expected], $sut->getCustomers());
        $this->assertTrue($sut->hasCustomers());

        $expected2 = new Customer();
        $expected2->setName('foo-bar2');

        $sut->addCustomer($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getCustomers());

        $sut->setCustomer(null);
        $this->assertNull($sut->getCustomer());
        $this->assertFalse($sut->hasCustomers());

        // make sure int is allowed as well
        $sut->setCustomers([99]);
        $this->assertEquals(99, $sut->getCustomer());
        $this->assertEquals([99], $sut->getCustomers());
    }

    protected function assertProject(ActivityQuery $sut)
    {
        $this->assertNull($sut->getProject());
        $this->assertEquals([], $sut->getProjects());
        $this->assertFalse($sut->hasProjects());

        $expected = new Project();
        $expected->setName('foo-bar');

        $sut->setProject($expected);
        $this->assertEquals($expected, $sut->getProject());

        $sut->setProjects([]);
        $this->assertEquals([], $sut->getProjects());

        $sut->addProject($expected);
        $this->assertEquals([$expected], $sut->getProjects());
        $this->assertTrue($sut->hasProjects());

        $expected2 = new Project();
        $expected2->setName('foo-bar2');

        $sut->addProject($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getProjects());

        $sut->setProject(null);
        $this->assertNull($sut->getProject());
        $this->assertFalse($sut->hasProjects());

        // make sure int is allowed as well
        $sut->setProjects([99]);
        $this->assertEquals(99, $sut->getProject());
        $this->assertEquals([99], $sut->getProjects());
    }
}
