<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Activity;
use App\Entity\Bookmark;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Form\Model\DateRange;
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
    public function testQuery(): void
    {
        $this->assertBaseQuery(new BaseQuery());
        $this->assertResetByFormError(new BaseQuery());
        $this->assertFilter(new BaseQuery());
    }

    protected function assertResetByFormError(BaseQuery $sut, $orderBy = 'id', $order = 'ASC'): void
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

    protected function assertBaseQuery(BaseQuery $sut, $orderBy = 'id', $order = BaseQuery::ORDER_ASC): void
    {
        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, $orderBy);
        $this->assertOrder($sut, $order);
        $this->assertTeams($sut);
        $this->assertBookmark($sut);
    }

    private function getFormBuilder(string $name): FormBuilder
    {
        return new FormBuilder($name, null, new EventDispatcher(), $this->createMock(FormFactoryInterface::class), []);
    }

    protected function resetByFormError(BaseQuery $sut, array $invalidFields): void
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

    protected function assertTeams(BaseQuery $sut): void
    {
        self::assertEmpty($sut->getTeams());

        self::assertInstanceOf(BaseQuery::class, $sut->addTeam(new Team('foo')));
        self::assertEquals(1, \count($sut->getTeams()));

        $sut->setTeams(null);
        self::assertEmpty($sut->getTeams());
        $sut->setTeams([]);
        self::assertEmpty($sut->getTeams());

        $team = new Team('foo');
        self::assertInstanceOf(BaseQuery::class, $sut->setTeams([$team]));
        self::assertEquals(1, \count($sut->getTeams()));
        /* @phpstan-ignore-next-line  */
        self::assertSame($team, $sut->getTeams()[0]);
    }

    protected function assertFilter(BaseQuery $sut): void
    {
        self::assertEquals(0, $sut->countFilter());
        $sut->setSearchTerm(new SearchTerm('sdfsdf'));
        self::assertEquals(1, $sut->countFilter());
        $sut->setPageSize(22);
        self::assertEquals(2, $sut->countFilter());
        $sut->setPage(2);
        self::assertEquals(2, $sut->countFilter());
        $sut->setOrderBy('foo');
        self::assertEquals(3, $sut->countFilter());
        $sut->setOrder(BaseQuery::ORDER_DESC);
        self::assertEquals(4, $sut->countFilter());
        $sut->setOrder(BaseQuery::ORDER_ASC);
        self::assertEquals(3, $sut->countFilter());
        $sut->setOrder(BaseQuery::ORDER_DESC);
        self::assertEquals(4, $sut->countFilter());

        self::assertTrue($sut->matchesFilter('page', 2));
        self::assertFalse($sut->isDefaultFilter('page'));

        $sut->resetFilter();
        self::assertEquals(0, $sut->countFilter());

        self::assertEquals(1, $sut->getPage());
        self::assertTrue($sut->matchesFilter('page', 1));
        self::assertTrue($sut->isDefaultFilter('page'));
        self::assertFalse($sut->isDefaultFilter('foo'));
    }

    protected function assertBookmark(BaseQuery $sut): void
    {
        $bookmark = new Bookmark();
        self::assertNull($sut->getBookmark());
        self::assertFalse($sut->hasBookmark());
        $sut->setBookmark($bookmark);
        self::assertSame($bookmark, $sut->getBookmark());
        self::assertTrue($sut->hasBookmark());
        self::assertFalse($sut->isBookmarkSearch());
        $sut->flagAsBookmarkSearch();
        self::assertTrue($sut->isBookmarkSearch());
    }

    protected function assertPage(BaseQuery $sut): void
    {
        self::assertEquals(1, $sut->getPage());

        $sut->setPage(42);
        self::assertEquals(42, $sut->getPage());
    }

    protected function assertPageSize(BaseQuery $sut): void
    {
        self::assertEquals(BaseQuery::DEFAULT_PAGESIZE, $sut->getPageSize());

        $sut->setPageSize(100);
        self::assertEquals(100, $sut->getPageSize());
    }

    protected function assertOrderBy(BaseQuery $sut, $column = 'id'): void
    {
        self::assertEquals($column, $sut->getOrderBy());

        $sut->setOrderBy('foo');
        self::assertEquals('foo', $sut->getOrderBy());
    }

    protected function assertOrder(BaseQuery $sut, $order = BaseQuery::ORDER_ASC): void
    {
        self::assertEquals($order, $sut->getOrder());

        $sut->setOrder('foo');
        self::assertEquals($order, $sut->getOrder());

        $sut->setOrder(BaseQuery::ORDER_ASC);
        self::assertEquals(BaseQuery::ORDER_ASC, $sut->getOrder());

        $sut->setOrder(BaseQuery::ORDER_DESC);
        self::assertEquals(BaseQuery::ORDER_DESC, $sut->getOrder());
    }

    protected function assertSearchTerm(BaseQuery $sut): void
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

    protected function assertActivity(TimesheetQuery $sut): void
    {
        $this->assertEquals([], $sut->getActivities());
        $this->assertFalse($sut->hasActivities());

        $expected = new Activity();
        $expected->setName('foo-bar');

        $sut->addActivity($expected);
        $this->assertEquals([$expected], $sut->getActivities());
        $this->assertTrue($sut->hasActivities());

        $expected2 = new Activity();
        $expected2->setName('foo-bar2');

        $sut->addActivity($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getActivities());
        $this->assertEquals([], $sut->getActivityIds());

        $sut->setActivities([]);
        $this->assertEquals([], $sut->getActivities());
        $this->assertFalse($sut->hasActivities());

        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(13);
        $sut->addActivity($activity);

        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(27);
        $sut->addActivity($activity);

        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(null);
        $sut->addActivity($activity);

        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(27);
        $sut->addActivity($activity);

        $this->assertEquals([13, 27], $sut->getActivityIds());
    }

    protected function assertCustomer(ProjectQuery $sut): void
    {
        $this->assertEquals([], $sut->getCustomers());
        $this->assertFalse($sut->hasCustomers());

        $expected = new Customer('foo-bar');

        $sut->addCustomer($expected);
        $this->assertEquals([$expected], $sut->getCustomers());
        $this->assertTrue($sut->hasCustomers());

        $expected2 = new Customer('foo-bar2');

        $sut->addCustomer($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getCustomers());
        $this->assertEquals([], $sut->getCustomerIds());

        $sut->setCustomers([]);
        $this->assertEquals([], $sut->getCustomers());
        $this->assertFalse($sut->hasCustomers());

        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(13);
        $sut->addCustomer($customer);

        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(27);
        $sut->addCustomer($customer);

        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(null);
        $sut->addCustomer($customer);

        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(27);
        $sut->addCustomer($customer);

        $this->assertEquals([13, 27], $sut->getCustomerIds());
    }

    protected function assertProject(ActivityQuery $sut): void
    {
        $this->assertEquals([], $sut->getProjects());
        $this->assertFalse($sut->hasProjects());

        $expected = new Project();
        $expected->setName('foo-bar');

        $sut->setProjects([]);
        $this->assertEquals([], $sut->getProjects());

        $sut->addProject($expected);
        $this->assertEquals([$expected], $sut->getProjects());
        $this->assertTrue($sut->hasProjects());

        $expected2 = new Project();
        $expected2->setName('foo-bar2');

        $sut->addProject($expected2);
        $this->assertEquals([$expected, $expected2], $sut->getProjects());
        $this->assertEquals([], $sut->getProjectIds());

        $sut->setProjects([]);
        $this->assertFalse($sut->hasProjects());

        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(13);
        $sut->addProject($project);

        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(27);
        $sut->addProject($project);

        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(null);
        $sut->addProject($project);

        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(27);
        $sut->addProject($project);

        $this->assertEquals([13, 27], $sut->getProjectIds());
    }

    protected function assertDateRangeTrait($sut): void
    {
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());

        $dateRange = new DateRange();
        $sut->setDateRange($dateRange);

        self::assertSame($dateRange, $sut->getDateRange());
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getEnd());

        $begin = new \DateTime('2013-11-23 13:45:07');
        $end = new \DateTime('2014-01-01 23:45:11');
        $dateRange->setBegin($begin);
        $dateRange->setEnd($end);

        self::assertSame($begin, $sut->getDateRange()->getBegin());
        self::assertSame($begin, $sut->getBegin());
        self::assertSame($end, $sut->getDateRange()->getEnd());
        self::assertSame($end, $sut->getEnd());
    }
}
