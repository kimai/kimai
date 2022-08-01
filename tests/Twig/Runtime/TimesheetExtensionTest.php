<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\FavoriteTimesheet;
use App\Repository\TimesheetRepository;
use App\Timesheet\FavoriteRecordService;
use App\Twig\Runtime\TimesheetExtension;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Twig\Runtime\TimesheetExtension
 */
class TimesheetExtensionTest extends TestCase
{
    public function testActiveEntries()
    {
        $entries = [new Timesheet(), new Timesheet()];

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getActiveEntries')->willReturn($entries);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $service = new FavoriteRecordService($repository, $dispatcher);

        $sut = new TimesheetExtension($repository, $service);
        self::assertEquals($entries, $sut->activeEntries(new User()));
    }

    public function testRecentEntries()
    {
        $entries = [new Timesheet(), new Timesheet()];
        $expected = [
            new FavoriteTimesheet($entries[0], false),
            new FavoriteTimesheet($entries[1], false),
        ];

        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getRecentActivities')->willReturn($entries);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $service = new FavoriteRecordService($repository, $dispatcher);

        $sut = new TimesheetExtension($repository, $service);
        $favorites = $sut->favoriteEntries(new User());
        self::assertCount(2, $favorites);
        self::assertEquals($entries[0], $favorites[0]->getTimesheet());
        self::assertFalse($favorites[0]->isFavorite());
        self::assertEquals($entries[1], $favorites[1]->getTimesheet());
        self::assertFalse($favorites[1]->isFavorite());
    }
}
