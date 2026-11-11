<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Entity\User;
use App\Model\DailyStatistic;
use App\Model\MonthlyStatistic;
use App\Repository\Query\TimesheetStatisticQuery;
use App\Tests\KernelTestTrait;
use App\Tests\TeamRestrictedTimesheetTrait;
use App\Timesheet\TimesheetStatisticService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(TimesheetStatisticService::class)]
#[Group('integration')]
class TimesheetStatisticServiceTest extends KernelTestCase
{
    use KernelTestTrait;
    use TeamRestrictedTimesheetTrait;

    private function getSut(): TimesheetStatisticService
    {
        $service = self::getContainer()->get(TimesheetStatisticService::class);
        self::assertInstanceOf(TimesheetStatisticService::class, $service);

        return $service;
    }

    /**
     * @param array<User> $users
     */
    private function createQuery(array $users, ?User $currentUser): TimesheetStatisticQuery
    {
        $query = new TimesheetStatisticQuery(new \DateTime('2020-05-01 00:00:00'), new \DateTime('2020-05-31 23:59:59'), $users);
        $query->setCurrentUser($currentUser);

        return $query;
    }

    /**
     * @param array<DailyStatistic|MonthlyStatistic> $stats
     * @return array<int, int>
     */
    private function getDurationByUser(array $stats): array
    {
        $durations = [];
        foreach ($stats as $stat) {
            $id = $stat->getUser()->getId();
            self::assertIsInt($id);
            $durations[$id] = 0;
            foreach ($stat->getData() as $date) {
                $durations[$id] += $date->getTotalDuration();
            }
        }

        return $durations;
    }

    /**
     * @param array<string, array<string, array{activities: array<string, mixed>}>> $stats
     * @return array<int, int>
     */
    private function countActivitiesByUser(array $stats): array
    {
        $amount = [];
        foreach ($stats as $userId => $projects) {
            $amount[(int) $userId] = 0;
            foreach ($projects as $project) {
                $amount[(int) $userId] += \count($project['activities']);
            }
        }

        return $amount;
    }

    public function testStatisticsWithoutCurrentUserAreNotLimited(): void
    {
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();
        $query = $this->createQuery([$teamlead, $member], null);
        $expected = [$teamlead->getId() => 3600, $member->getId() => 10800];

        $sut = $this->getSut();

        self::assertEquals($expected, $this->getDurationByUser($sut->getDailyStatistics($query)));
        self::assertEquals($expected, $this->getDurationByUser($sut->getMonthlyStats($query)));
        self::assertEquals([$teamlead->getId() => 1, $member->getId() => 3], $this->countActivitiesByUser($sut->getDailyStatisticsGrouped($query)));
        self::assertEquals([$teamlead->getId() => 1, $member->getId() => 3], $this->countActivitiesByUser($sut->getMonthlyStatisticsGrouped($query)));
    }

    /**
     * A teamlead sees all of his own records, but from his team members only the ones
     * for customers, projects and activities which he can access himself.
     */
    public function testStatisticsForTeamleadAreLimited(): void
    {
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();
        $query = $this->createQuery([$teamlead, $member], $teamlead);
        $expected = [$teamlead->getId() => 3600, $member->getId() => 3600];

        $sut = $this->getSut();

        self::assertEquals($expected, $this->getDurationByUser($sut->getDailyStatistics($query)));
        self::assertEquals($expected, $this->getDurationByUser($sut->getMonthlyStats($query)));
        self::assertEquals([$teamlead->getId() => 1, $member->getId() => 1], $this->countActivitiesByUser($sut->getDailyStatisticsGrouped($query)));
        self::assertEquals([$teamlead->getId() => 1, $member->getId() => 1], $this->countActivitiesByUser($sut->getMonthlyStatisticsGrouped($query)));
    }

    public function testStatisticsForOwnRecordsAreNotLimited(): void
    {
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();
        $query = $this->createQuery([$member], $member);
        $expected = [$member->getId() => 10800];

        $sut = $this->getSut();

        self::assertEquals($expected, $this->getDurationByUser($sut->getDailyStatistics($query)));
        self::assertEquals($expected, $this->getDurationByUser($sut->getMonthlyStats($query)));
        self::assertEquals([$member->getId() => 3], $this->countActivitiesByUser($sut->getDailyStatisticsGrouped($query)));
        self::assertEquals([$member->getId() => 3], $this->countActivitiesByUser($sut->getMonthlyStatisticsGrouped($query)));
    }

    public function testStatisticsForUserWhoCanSeeAllDataAreNotLimited(): void
    {
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();
        $admin = $this->getUserByRole(User::ROLE_SUPER_ADMIN);
        $admin->initCanSeeAllData(true);
        $query = $this->createQuery([$teamlead, $member], $admin);
        $expected = [$teamlead->getId() => 3600, $member->getId() => 10800];

        $sut = $this->getSut();

        self::assertEquals($expected, $this->getDurationByUser($sut->getDailyStatistics($query)));
        self::assertEquals($expected, $this->getDurationByUser($sut->getMonthlyStats($query)));
        self::assertEquals([$teamlead->getId() => 1, $member->getId() => 3], $this->countActivitiesByUser($sut->getDailyStatisticsGrouped($query)));
        self::assertEquals([$teamlead->getId() => 1, $member->getId() => 3], $this->countActivitiesByUser($sut->getMonthlyStatisticsGrouped($query)));
    }
}
