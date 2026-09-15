<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Entity\Customer;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\DateStatisticInterface;
use App\Repository\Query\TimesheetStatisticQuery;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\KernelTestTrait;
use App\Timesheet\TimesheetStatisticService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(TimesheetStatisticService::class)]
#[Group('integration')]
class TimesheetStatisticServiceTest extends KernelTestCase
{
    use KernelTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    private function getSut(): TimesheetStatisticService
    {
        /** @var TimesheetStatisticService $service */
        $service = self::getContainer()->get(TimesheetStatisticService::class);

        return $service;
    }

    private function createUserWithTimesheets(): User
    {
        $user = $this->getUserByRole(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(20);
        $fixture->setUser($user);
        $fixture->setStartDate(new \DateTime('2020-01-01'));
        $this->importFixture($fixture);

        return $user;
    }

    /**
     * A range that is guaranteed to contain every imported timesheet.
     *
     * @return array{0: \DateTime, 1: \DateTime}
     */
    private function getFullRange(): array
    {
        return [new \DateTime('2000-01-01'), new \DateTime('2100-01-01')];
    }

    /**
     * @param DateStatisticInterface[] $statistics
     */
    private function getTotalDuration(array $statistics): int
    {
        $duration = 0;
        foreach ($statistics as $statistic) {
            foreach ($statistic->getData() as $date) {
                $duration += $date->getTotalDuration();
            }
        }

        return $duration;
    }

    private function getCustomerWithTimesheets(User $user): Customer
    {
        $timesheet = $this->getEntityManager()->getRepository(Timesheet::class)->findOneBy(['user' => $user->getId()]);
        self::assertInstanceOf(Timesheet::class, $timesheet);

        $project = $timesheet->getProject();
        self::assertNotNull($project);

        $customer = $project->getCustomer();
        self::assertInstanceOf(Customer::class, $customer);

        return $customer;
    }

    public function testGetDailyStatisticsFilteredByCustomer(): void
    {
        $user = $this->createUserWithTimesheets();
        [$begin, $end] = $this->getFullRange();
        $sut = $this->getSut();

        $query = new TimesheetStatisticQuery($begin, $end, [$user]);
        $total = $this->getTotalDuration($sut->getDailyStatistics($query));
        self::assertGreaterThan(0, $total);

        $query = new TimesheetStatisticQuery($begin, $end, [$user]);
        $query->setCustomer($this->getCustomerWithTimesheets($user));
        $filtered = $this->getTotalDuration($sut->getDailyStatistics($query));

        self::assertGreaterThan(0, $filtered);
        self::assertLessThanOrEqual($total, $filtered);
    }

    public function testGetDailyStatisticsWithCustomerWithoutTimesheets(): void
    {
        $user = $this->createUserWithTimesheets();
        [$begin, $end] = $this->getFullRange();

        $customers = $this->importFixture(new CustomerFixtures(1));

        $query = new TimesheetStatisticQuery($begin, $end, [$user]);
        $query->setCustomer($customers[0]);

        $statistics = $this->getSut()->getDailyStatistics($query);

        // one statistic per user is always returned, but it must not contain any duration
        self::assertCount(1, $statistics);
        self::assertEquals(0, $this->getTotalDuration($statistics));
    }

    public function testGetMonthlyStatsFilteredByCustomer(): void
    {
        $user = $this->createUserWithTimesheets();
        [$begin, $end] = $this->getFullRange();
        $sut = $this->getSut();

        $query = new TimesheetStatisticQuery($begin, $end, [$user]);
        $total = $this->getTotalDuration($sut->getMonthlyStats($query));
        self::assertGreaterThan(0, $total);

        $query = new TimesheetStatisticQuery($begin, $end, [$user]);
        $query->setCustomer($this->getCustomerWithTimesheets($user));
        $filtered = $this->getTotalDuration($sut->getMonthlyStats($query));

        self::assertGreaterThan(0, $filtered);
        self::assertLessThanOrEqual($total, $filtered);
    }

    public function testGetMonthlyStatsWithCustomerWithoutTimesheets(): void
    {
        $user = $this->createUserWithTimesheets();
        [$begin, $end] = $this->getFullRange();

        $customers = $this->importFixture(new CustomerFixtures(1));

        $query = new TimesheetStatisticQuery($begin, $end, [$user]);
        $query->setCustomer($customers[0]);

        $statistics = $this->getSut()->getMonthlyStats($query);

        self::assertCount(1, $statistics);
        self::assertEquals(0, $this->getTotalDuration($statistics));
    }
}
