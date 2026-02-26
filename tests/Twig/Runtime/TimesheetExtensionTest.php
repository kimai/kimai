<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\TicktackExcludeEvent;
use App\Repository\BookmarkRepository;
use App\Repository\TimesheetRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Timesheet\FavoriteRecordService;
use App\Twig\Runtime\TimesheetExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(TimesheetExtension::class)]
class TimesheetExtensionTest extends TestCase
{
    public function testActiveEntries(): void
    {
        $entries = [new Timesheet(), new Timesheet()];

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getActiveEntries')->willReturn($entries);

        $bookmarks = $this->createMock(BookmarkRepository::class);
        $service = new FavoriteRecordService($repository, $bookmarks);
        $configuration = SystemConfigurationFactory::createStub([]);
        $dispatcher = new EventDispatcher();

        $sut = new TimesheetExtension($repository, $service, $configuration, $dispatcher);
        self::assertEquals($entries, $sut->activeEntries(new User()));
    }

    public function testActiveEntriesHardLimit(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $bookmarks = $this->createMock(BookmarkRepository::class);
        $service = new FavoriteRecordService($repository, $bookmarks);
        $configuration = SystemConfigurationFactory::createStub(['timesheet' => ['active_entries' => ['hard_limit' => 3]]]);
        $dispatcher = new EventDispatcher();

        $sut = new TimesheetExtension($repository, $service, $configuration, $dispatcher);
        self::assertEquals(3, $sut->activeEntriesHardLimit());
    }

    public function testTicktackExcludesEmpty(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $bookmarks = $this->createMock(BookmarkRepository::class);
        $service = new FavoriteRecordService($repository, $bookmarks);
        $configuration = SystemConfigurationFactory::createStub([]);
        $dispatcher = new EventDispatcher();

        $sut = new TimesheetExtension($repository, $service, $configuration, $dispatcher);
        self::assertSame([], $sut->ticktackExcludes());
    }

    public function testTicktackExcludesWithSubscriber(): void
    {
        $repository = $this->createMock(TimesheetRepository::class);
        $bookmarks = $this->createMock(BookmarkRepository::class);
        $service = new FavoriteRecordService($repository, $bookmarks);
        $configuration = SystemConfigurationFactory::createStub([]);
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(TicktackExcludeEvent::class, function (TicktackExcludeEvent $event) {
            $event->addExclude(['project' => 5, 'activity' => 3]);
        });

        $sut = new TimesheetExtension($repository, $service, $configuration, $dispatcher);
        $excludes = $sut->ticktackExcludes();
        self::assertCount(1, $excludes);
        self::assertSame(['project' => 5, 'activity' => 3], $excludes[0]);
    }

    public function testTicktackEntriesFiltersExcluded(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(5);
        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(3);

        $excluded = $this->createMock(Timesheet::class);
        $excluded->method('getProject')->willReturn($project);
        $excluded->method('getActivity')->willReturn($activity);

        $kept = new Timesheet();

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getActiveEntries')->willReturn([$kept, $excluded]);

        $bookmarks = $this->createMock(BookmarkRepository::class);
        $service = new FavoriteRecordService($repository, $bookmarks);
        $configuration = SystemConfigurationFactory::createStub([]);
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(TicktackExcludeEvent::class, function (TicktackExcludeEvent $event) {
            $event->addExclude(['project' => 5, 'activity' => 3]);
        });

        $sut = new TimesheetExtension($repository, $service, $configuration, $dispatcher);
        $result = $sut->ticktackEntries(new User());
        self::assertCount(1, $result);
        self::assertSame($kept, $result[0]);
    }

    public function testTicktackEntriesNoExcludes(): void
    {
        $entries = [new Timesheet(), new Timesheet()];

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getActiveEntries')->willReturn($entries);

        $bookmarks = $this->createMock(BookmarkRepository::class);
        $service = new FavoriteRecordService($repository, $bookmarks);
        $configuration = SystemConfigurationFactory::createStub([]);
        $dispatcher = new EventDispatcher();

        $sut = new TimesheetExtension($repository, $service, $configuration, $dispatcher);
        self::assertSame($entries, $sut->ticktackEntries(new User()));
    }

    public function testRecentEntries(): void
    {
        $timesheet1 = $this->createMock(Timesheet::class);
        $timesheet1->method('getId')->willReturn(1);

        $timesheet2 = $this->createMock(Timesheet::class);
        $timesheet2->method('getId')->willReturn(2);

        $entries = [$timesheet1, $timesheet2];

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('findTimesheetsById')->willReturn($entries);
        $repository->method('getRecentActivityIds')->willReturn([1, 2]);

        $bookmarks = $this->createMock(BookmarkRepository::class);
        $service = new FavoriteRecordService($repository, $bookmarks);
        $configuration = SystemConfigurationFactory::createStub([]);
        $dispatcher = new EventDispatcher();

        $sut = new TimesheetExtension($repository, $service, $configuration, $dispatcher);
        $favorites = $sut->favoriteEntries(new User());
        self::assertCount(2, $favorites);
        self::assertEquals($entries[0], $favorites[0]->getTimesheet());
        self::assertFalse($favorites[0]->isFavorite());
        self::assertEquals($entries[1], $favorites[1]->getTimesheet());
        self::assertFalse($favorites[1]->isFavorite());
    }
}
