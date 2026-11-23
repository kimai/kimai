<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\User;
use App\Export\DefaultTemplate;
use App\Repository\Query\TimesheetQuery;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(DefaultTemplate::class)]
class DefaultTemplateTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $template = new DefaultTemplate($this->createMock(EventDispatcherInterface::class), 'foo');
        self::assertEquals('foo', $template->getId());
        self::assertEquals('default', $template->getTitle());
        self::assertEquals('en', $template->getLocale());
        self::assertEquals([], $template->getOptions());

        $columns = [
            'date',
            'begin',
            'end',
            'duration',
            'currency',
            'rate',
            'internal_rate',
            'hourly_rate',
            'fixed_rate',
            'user.alias',
            'user.name',
            'user.email',
            'user.account_number',
            'customer.name',
            'project.name',
            'activity.name',
            'description',
            'billable',
            'tags',
            'type',
            'category',
            'customer.number',
            'project.number',
            'customer.vat_id',
            'project.order_number',
        ];
        self::assertEquals($columns, $template->getColumns(new TimesheetQuery()));
    }

    public function testFullConstructorWithDecimalDurationAndMetaColumns(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $template = new DefaultTemplate($dispatcher, 'hello', null, 'world');
        self::assertEquals('hello', $template->getId());
        self::assertEquals('world', $template->getTitle());
        self::assertNull($template->getLocale());
        self::assertEquals([], $template->getOptions());

        $user = new User();
        $user->setPreferenceValue('export_decimal', true);
        $query = new TimesheetQuery();
        $query->setCurrentUser($user);

        $columns = [
            'date',
            'begin',
            'end',
            'duration_decimal',
            'currency',
            'rate',
            'internal_rate',
            'hourly_rate',
            'fixed_rate',
            'user.alias',
            'user.name',
            'user.email',
            'user.account_number',
            'customer.name',
            'project.name',
            'activity.name',
            'description',
            'billable',
            'tags',
            'type',
            'category',
            'customer.number',
            'project.number',
            'customer.vat_id',
            'project.order_number',
            'timesheet.meta.foo',
            'timesheet.meta.foo2',
            'customer.meta.customer-foo',
            'project.meta.project-foo',
            'project.meta.project-foo2',
            'activity.meta.activity-foo',
            'user.meta.mypref',
        ];
        self::assertEquals($columns, $template->getColumns($query));
    }
}
