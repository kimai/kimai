<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\ActivityMeta;
use App\Entity\CustomerMeta;
use App\Entity\ProjectMeta;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\AbstractMetaDisplayEvent;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\ColumnConverter;
use App\Export\DefaultTemplate;
use App\Export\Template;
use App\Repository\Query\TimesheetQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(ColumnConverter::class)]
class ColumnConverterTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $dispatcher = new EventDispatcher();
        $security = $this->createMock(Security::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $template = new DefaultTemplate($dispatcher, 'foo');
        $query = new TimesheetQuery();

        $sut = new ColumnConverter($dispatcher, $security, $logger);
        $columns = $sut->getColumns($template, $query);

        $expected = [
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

        self::assertEquals($expected, array_keys($columns));
    }

    public function testSkipsRate(): void
    {
        $user = new User();
        $dispatcher = $this->createMock(EventDispatcher::class);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $template = new DefaultTemplate($dispatcher, 'foo');
        $query = new TimesheetQuery();
        $query->setUser($user);

        $sut = new ColumnConverter($dispatcher, $security, $logger);
        $columns = $sut->getColumns($template, $query);

        $expected = [
            'date',
            'begin',
            'end',
            'duration',
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

        self::assertEquals($expected, array_keys($columns));
    }

    public function testAppliesQueryTimezoneToDateAndTimeColumns(): void
    {
        $dispatcher = new EventDispatcher();
        $security = $this->createMock(Security::class);

        $template = new DefaultTemplate($dispatcher, 'foo');
        $query = new TimesheetQuery();
        $query->setTimezone(new \DateTimeZone('America/New_York'));

        $sut = new ColumnConverter($dispatcher, $security);
        $columns = $sut->getColumns($template, $query);

        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime('2026-08-21 01:00:00', new \DateTimeZone('Europe/Berlin')));
        $timesheet->setEnd(new \DateTime('2026-08-21 02:00:00', new \DateTimeZone('Europe/Berlin')));

        $date = $columns['date']->getValue($timesheet);
        self::assertInstanceOf(\DateTimeInterface::class, $date);
        self::assertEquals('2026-08-20', $date->format('Y-m-d'));
        self::assertEquals('America/New_York', $date->getTimezone()->getName());

        self::assertEquals('19:00', $columns['begin']->getValue($timesheet));
        self::assertEquals('20:00', $columns['end']->getValue($timesheet));

        self::assertEquals('export.date_column', $columns['date']->getHeader());
        self::assertEquals(['%timezone%' => 'America/New_York'], $columns['date']->getHeaderParams());
    }

    public function testAppliesTheSameQueryTimezoneRegardlessOfWhichUsersEntryIsRendered(): void
    {
        $dispatcher = new EventDispatcher();
        $security = $this->createMock(Security::class);

        $template = new DefaultTemplate($dispatcher, 'foo');
        $query = new TimesheetQuery();
        $query->setTimezone(new \DateTimeZone('America/New_York'));

        $sut = new ColumnConverter($dispatcher, $security);
        $columns = $sut->getColumns($template, $query);

        // two entries, each recorded in a different user's own timezone
        $entryFromBerlinUser = new Timesheet();
        $entryFromBerlinUser->setBegin(new \DateTime('2026-08-21 01:00:00', new \DateTimeZone('Europe/Berlin')));

        $entryFromTokyoUser = new Timesheet();
        $entryFromTokyoUser->setBegin(new \DateTime('2026-08-21 08:00:00', new \DateTimeZone('Asia/Tokyo')));

        $dateFromBerlinUser = $columns['date']->getValue($entryFromBerlinUser);
        $dateFromTokyoUser = $columns['date']->getValue($entryFromTokyoUser);
        self::assertInstanceOf(\DateTimeInterface::class, $dateFromBerlinUser);
        self::assertInstanceOf(\DateTimeInterface::class, $dateFromTokyoUser);

        // Europe/Berlin 2026-08-21 01:00 and Asia/Tokyo 2026-08-21 08:00 are the same instant
        self::assertEquals('2026-08-20', $dateFromBerlinUser->format('Y-m-d'));
        self::assertEquals('2026-08-20', $dateFromTokyoUser->format('Y-m-d'));
        self::assertEquals('America/New_York', $dateFromBerlinUser->getTimezone()->getName());
        self::assertEquals('America/New_York', $dateFromTokyoUser->getTimezone()->getName());
    }

    public function testKeepsPlainDateHeaderWithoutQueryTimezone(): void
    {
        $dispatcher = new EventDispatcher();
        $security = $this->createMock(Security::class);

        $template = new DefaultTemplate($dispatcher, 'foo');
        $query = new TimesheetQuery();

        $sut = new ColumnConverter($dispatcher, $security);
        $columns = $sut->getColumns($template, $query);

        self::assertEquals('date', $columns['date']->getHeader());
        self::assertEquals([], $columns['date']->getHeaderParams());
    }

    public function testWithMetaFields(): void
    {
        $user = new User();

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (AbstractMetaDisplayEvent|UserPreferenceDisplayEvent $event) {
            if ($event instanceof CustomerMetaDisplayEvent) {
                $event->addField((new CustomerMeta())->setName('foo_meta')->setIsVisible(true));
                $event->addField((new CustomerMeta())->setName('bar_meta')->setIsVisible(true));
            } elseif ($event instanceof ProjectMetaDisplayEvent) {
                $event->addField((new ProjectMeta())->setName('visible_project')->setIsVisible(true));
                $event->addField((new ProjectMeta())->setName('hidden_project')->setIsVisible(false));
            } elseif ($event instanceof ActivityMetaDisplayEvent) {
                $event->addField((new ActivityMeta())->setName('activity_world')->setIsVisible(true));
                $event->addField((new ActivityMeta())->setName('hello_activity')->setIsVisible(false));
            } elseif ($event instanceof TimesheetMetaDisplayEvent) {
                $event->addField((new TimesheetMeta())->setName('timesheet_one')->setIsVisible(true));
                $event->addField((new TimesheetMeta())->setName('timesheet_two')->setIsVisible(false));
            } elseif ($event instanceof UserPreferenceDisplayEvent) {
                $event->addPreference((new UserPreference('user_acme'))->setEnabled(true));
                $event->addPreference((new UserPreference('user_foo'))->setEnabled(false));
            }

            return $event;
        });

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $template = new DefaultTemplate($dispatcher, 'foo');
        $query = new TimesheetQuery();
        $query->setUser($user);

        $sut = new ColumnConverter($dispatcher, $security, $logger);
        $columns = $sut->getColumns($template, $query);

        $expected = [
            'date',
            'begin',
            'end',
            'duration',
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
            'timesheet.meta.timesheet_one',
            'timesheet.meta.timesheet_two',
            'customer.meta.foo_meta',
            'customer.meta.bar_meta',
            'project.meta.visible_project',
            'project.meta.hidden_project',
            'activity.meta.activity_world',
            'activity.meta.hello_activity',
            'user.meta.user_acme',
            'user.meta.user_foo',
        ];

        self::assertEquals($expected, array_keys($columns));
    }

    public function testWithAllAndUnknownColumns(): void
    {
        $user = new User();

        $dispatcher = $this->createMock(EventDispatcher::class);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(3))->method('warning');

        $template = new Template('bar', 'foo');
        $template->setColumns([
            'id',
            'date',
            'begin',
            'end',
            'duration',
            'duration_decimal',
            'duration_seconds',
            'break',
            'break_decimal',
            'break_seconds',
            'exported',
            'currency',
            'rate',
            'internal_rate',
            'hourly_rate',
            'fixed_rate',
            'user.alias',
            'unknown.1',
            'activity.number',
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
            'project.tralalala',
            'project.number',
            'customer.vat_id',
            'customer.is.never,evil',
            'project.order_number',
        ]);
        $query = new TimesheetQuery();
        $query->setUser($user);

        $sut = new ColumnConverter($dispatcher, $security, $logger);
        $columns = $sut->getColumns($template, $query);

        $expected = [
            'id',
            'date',
            'begin',
            'end',
            'duration',
            'duration_decimal',
            'duration_seconds',
            'break',
            'break_decimal',
            'break_seconds',
            'exported',
            'currency',
            'rate',
            'internal_rate',
            'hourly_rate',
            'fixed_rate',
            'user.alias',
            'activity.number',
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

        self::assertEquals($expected, array_keys($columns));
    }
}
