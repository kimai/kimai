<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime;

use App\Entity\User;
use App\Event\WorkingTimeQueryStatsEvent;
use App\Event\WorkingTimeQueryUsersStatsEvent;
use App\Event\WorkingTimeYearEvent;
use App\Event\WorkingTimeYearsEvent;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\KernelTestTrait;
use App\Timesheet\DateTimeFactory;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeDay;
use App\WorkingTime\WorkingTimeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[CoversClass(WorkingTimeService::class)]
#[Group('integration')]
class WorkingTimeServiceTest extends KernelTestCase
{
    use KernelTestTrait;

    private function getService(): WorkingTimeService
    {
        $service = self::getContainer()->get(WorkingTimeService::class);
        self::assertInstanceOf(WorkingTimeService::class, $service);

        return $service;
    }

    private function prepareUser(string $role, int $hoursPerDay): User
    {
        $user = $this->getUserByRole($role);
        $user->setWorkContractMode(WorkingTimeModeDay::ID);
        foreach ([WorkingTimeCalculatorDay::WORK_HOURS_MONDAY, WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY, WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY, WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY, WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY] as $day) {
            $user->setPreferenceValue($day, (string) ($hoursPerDay * 3600));
        }
        $user->setWorkStartingDay(new \DateTime('2024-02-01'));
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    /**
     * @return array<User>
     */
    private function prepareData(): array
    {
        $user = $this->prepareUser(User::ROLE_USER, 8);
        $teamlead = $this->prepareUser(User::ROLE_TEAMLEAD, 6);
        $teamlead->setLastWorkingDay(new \DateTime('2024-09-30'));
        $this->getEntityManager()->flush();

        $fixture = new TimesheetFixtures($user, 20);
        $fixture->setStartDate('2024-03-01');
        $this->importFixture($fixture);

        $fixture = new TimesheetFixtures($teamlead, 15);
        $fixture->setStartDate('2024-06-01');
        $this->importFixture($fixture);

        // these must not be counted
        $fixture = new TimesheetFixtures($user, 5);
        $fixture->setStartDate('2023-06-01');
        $this->importFixture($fixture);

        $fixture = new TimesheetFixtures($this->getUserByRole(User::ROLE_ADMIN), 10);
        $fixture->setStartDate('2024-03-01');
        $this->importFixture($fixture);

        // approve March for the user, so the values are taken from the existing working times
        $service = $this->getService();
        $now = new \DateTime('2026-01-01');
        $year = $service->getYear($user, DateTimeFactory::createByUser($user)->createStartOfYear(new \DateTime('2024-03-01')), $now);
        $service->approveMonth($user, $year->getMonth(new \DateTime('2024-03-10')), $now, $this->getUserByRole(User::ROLE_ADMIN));

        return [$user, $teamlead];
    }

    public function testGetYearsReturnsSameResultsAsGetYear(): void
    {
        $users = $this->prepareData();
        $service = $this->getService();
        $now = new \DateTime('2026-01-01');

        $years = $service->getYears($users, new \DateTime('2024-05-17'), $now);

        self::assertCount(2, $years);

        foreach ($users as $user) {
            $userId = $user->getId();
            self::assertNotNull($userId);
            self::assertArrayHasKey($userId, $years);
            $multi = $years[$userId];
            self::assertSame($user, $multi->getUser());
            self::assertEquals('2024-01-01', $multi->getYear()->format('Y-m-d'));

            $single = $service->getYear($user, DateTimeFactory::createByUser($user)->createStartOfYear(new \DateTime('2024-05-17')), $now);

            self::assertEquals($single->getExpectedTime($now), $multi->getExpectedTime($now));
            self::assertEquals($single->getActualTime(), $multi->getActualTime());
            self::assertGreaterThan(0, $multi->getExpectedTime($now));
            self::assertGreaterThan(0, $multi->getActualTime());

            foreach ($single->getMonths() as $month) {
                $other = $multi->getMonth($month->getMonth());
                self::assertEquals($month->getExpectedTime($now), $other->getExpectedTime($now));
                self::assertEquals($month->getActualTime(), $other->getActualTime());
                self::assertEquals($month->isLocked(), $other->isLocked());
            }
        }

        // only the approved month is locked
        $userId = $users[0]->getId();
        $teamleadId = $users[1]->getId();
        self::assertNotNull($userId);
        self::assertNotNull($teamleadId);
        $userYear = $years[$userId];
        self::assertTrue($userYear->getMonth(new \DateTime('2024-03-10'))->isLocked());
        self::assertFalse($userYear->getMonth(new \DateTime('2024-04-10'))->isLocked());

        // employment period is respected
        self::assertEquals(0, $userYear->getMonth(new \DateTime('2024-01-10'))->getExpectedTime($now));
        $teamleadYear = $years[$teamleadId];
        self::assertEquals(0, $teamleadYear->getMonth(new \DateTime('2024-10-10'))->getExpectedTime($now));
        self::assertGreaterThan(0, $teamleadYear->getMonth(new \DateTime('2024-09-10'))->getExpectedTime($now));
    }

    public function testGetYearsDispatchesEvents(): void
    {
        $users = $this->prepareData();

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $counter = [
            WorkingTimeYearsEvent::class => 0,
            WorkingTimeYearEvent::class => 0,
            WorkingTimeQueryUsersStatsEvent::class => 0,
            WorkingTimeQueryStatsEvent::class => 0,
        ];
        $years = null;
        foreach (array_keys($counter) as $eventName) {
            $dispatcher->addListener($eventName, function (object $event) use (&$counter, &$years, $eventName): void {
                $counter[$eventName]++;
                if ($event instanceof WorkingTimeYearsEvent) {
                    $years = $event->getYears();
                }
            });
        }

        $result = $this->getService()->getYears($users, new \DateTime('2024-05-17'), new \DateTime('2026-01-01'));

        self::assertEquals(1, $counter[WorkingTimeYearsEvent::class]);
        self::assertEquals(2, $counter[WorkingTimeYearEvent::class]);
        // statistics are loaded with one query for all users
        self::assertEquals(1, $counter[WorkingTimeQueryUsersStatsEvent::class]);
        self::assertEquals(0, $counter[WorkingTimeQueryStatsEvent::class]);
        self::assertSame(array_values($result), $years);
    }

    public function testGetYearsWithoutUsers(): void
    {
        $service = $this->getService();

        self::assertSame([], $service->getYears([], new \DateTime('2024-05-17'), new \DateTime('2026-01-01')));
        // users without ID are ignored
        self::assertSame([], $service->getYears([new User()], new \DateTime('2024-05-17'), new \DateTime('2026-01-01')));
    }
}
