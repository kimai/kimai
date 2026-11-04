<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Export\ExportablePreviewResult;
use App\Export\ExportableResultStatisticInterface;
use App\Export\TimesheetExportRepository;
use App\Repository\CustomerRepository;
use App\Repository\Query\ExportQuery;
use App\Repository\Query\TimesheetQueryHint;
use App\Repository\TimesheetRepository;
use App\Tests\Repository\AbstractRepositoryTestCase;
use App\Utils\SearchTerm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(TimesheetExportRepository::class)]
#[Group('integration')]
class TimesheetExportRepositoryIntegrationTest extends AbstractRepositoryTestCase
{
    private const BEGIN = '2015-06-15 10:00:00';

    private Customer $customerA;
    private Customer $customerB;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $em = $this->getEntityManager();
        $activity = $em->getRepository(Activity::class)->find(1);
        self::assertInstanceOf(Activity::class, $activity);

        $this->user = $this->getUserByRole(User::ROLE_USER);
        $this->customerA = new Customer('Export statistic A');
        $this->customerA->setCountry('DE');
        $this->customerA->setCurrency('EUR');
        $this->customerA->setTimezone('Europe/Berlin');
        $this->customerB = new Customer('Export statistic B');
        $this->customerB->setCountry('DE');
        $this->customerB->setCurrency('EUR');
        $this->customerB->setTimezone('Europe/Berlin');
        $em->persist($this->customerA);
        $em->persist($this->customerB);

        $projectA = new Project();
        $projectA->setName('Export statistic A');
        $projectA->setCustomer($this->customerA);
        $projectB = new Project();
        $projectB->setName('Export statistic B');
        $projectB->setCustomer($this->customerB);
        $em->persist($projectA);
        $em->persist($projectB);

        // the first three timesheets have several custom-fields, but not "foo" and "bar"
        $this->createTimesheet($activity, $projectA, 3600, 100.0, ['one' => '1', 'two' => '2', 'three' => '3']);
        $this->createTimesheet($activity, $projectA, 1800, 50.0, ['one' => '1', 'two' => '2']);
        $this->createTimesheet($activity, $projectB, 900, 25.0, ['one' => '1', 'two' => '2']);
        // these must only be found if the search does not exclude them
        $this->createTimesheet($activity, $projectA, 600, 10.0, ['foo' => 'value', 'one' => '1', 'two' => '2']);
        $this->createTimesheet($activity, $projectB, 300, 5.0, ['foo' => 'value', 'bar' => 'value']);

        $em->flush();
        $em->clear();
    }

    /**
     * @param array<string, string> $metaFields
     */
    private function createTimesheet(Activity $activity, Project $project, int $duration, float $rate, array $metaFields): void
    {
        $timesheet = new Timesheet();
        $timesheet->setBegin(new \DateTime(self::BEGIN));
        $timesheet->setEnd((new \DateTime(self::BEGIN))->modify('+' . $duration . ' seconds'));
        $timesheet->setDuration($duration);
        // the rate is re-calculated when the timesheet is saved: a fixed rate keeps it predictable
        $timesheet->setFixedRate($rate);
        $timesheet->setUser($this->user);
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);

        foreach ($metaFields as $name => $value) {
            $meta = new TimesheetMeta();
            $meta->setName($name);
            $meta->setValue($value);
            $timesheet->setMetaField($meta);
        }

        $this->getEntityManager()->persist($timesheet);
    }

    private function createSut(): TimesheetExportRepository
    {
        $em = $this->getEntityManager();

        $timesheetRepository = $em->getRepository(Timesheet::class);
        self::assertInstanceOf(TimesheetRepository::class, $timesheetRepository);
        $customerRepository = $em->getRepository(Customer::class);
        self::assertInstanceOf(CustomerRepository::class, $customerRepository);

        return new TimesheetExportRepository($timesheetRepository, $customerRepository);
    }

    private function createQuery(?string $searchTerm = null): ExportQuery
    {
        $query = new ExportQuery();
        $query->setUser($this->user);
        $query->setBegin(new \DateTime('2015-06-15 00:00:00'));
        $query->setEnd(new \DateTime('2015-06-15 23:59:59'));
        if ($searchTerm !== null) {
            $query->setSearchTerm(new SearchTerm($searchTerm));
        }

        return $query;
    }

    /**
     * @param array<ExportableResultStatisticInterface> $statistics
     * @return array<string, array{count: int, duration: int, rate: float}>
     */
    private function normalizeStatistic(array $statistics): array
    {
        $result = [];
        foreach ($statistics as $statistic) {
            $result[(string) $statistic->getCustomer()->getName()] = [
                'count' => $statistic->getCount(),
                'duration' => $statistic->getDuration(),
                'rate' => $statistic->getRate(),
            ];
        }
        ksort($result);

        return $result;
    }

    /**
     * @return array<string, array{0: string|null, 1: array<string, array{count: int, duration: int, rate: float}>}>
     */
    public static function getStatisticData(): array
    {
        $excluded = [
            'Export statistic A' => ['count' => 2, 'duration' => 5400, 'rate' => 150.0],
            'Export statistic B' => ['count' => 1, 'duration' => 900, 'rate' => 25.0],
        ];

        return [
            'without search' => [
                null,
                [
                    'Export statistic A' => ['count' => 3, 'duration' => 6000, 'rate' => 160.0],
                    'Export statistic B' => ['count' => 2, 'duration' => 1200, 'rate' => 30.0],
                ]
            ],
            'missing custom-field' => ['foo:~', $excluded],
            'empty custom-field' => ['foo:""', $excluded],
            'two missing custom-fields' => ['foo:~ bar:~', $excluded],
            'existing custom-field' => [
                'foo:*',
                [
                    'Export statistic A' => ['count' => 1, 'duration' => 600, 'rate' => 10.0],
                    'Export statistic B' => ['count' => 1, 'duration' => 300, 'rate' => 5.0],
                ]
            ],
            'custom-field value' => [
                'bar:value',
                [
                    'Export statistic B' => ['count' => 1, 'duration' => 300, 'rate' => 5.0],
                ]
            ],
        ];
    }

    /**
     * @param array<string, array{count: int, duration: int, rate: float}> $expected
     */
    #[DataProvider('getStatisticData')]
    public function testPreviewStatisticCountsEveryTimesheetOnce(?string $searchTerm, array $expected): void
    {
        $preview = $this->createSut()->getPreview($this->createQuery($searchTerm), 100);

        self::assertEquals($expected, $this->normalizeStatistic($preview->getStatistic()));
    }

    public function testPreviewStatisticRespectsEntityFilter(): void
    {
        $query = $this->createQuery('foo:~');
        $query->addCustomer($this->customerB);

        $preview = $this->createSut()->getPreview($query, 100);

        self::assertEquals(
            ['Export statistic B' => ['count' => 1, 'duration' => 900, 'rate' => 25.0]],
            $this->normalizeStatistic($preview->getStatistic())
        );
    }

    public function testPreviewStatisticRespectsTeamPermissions(): void
    {
        $em = $this->getEntityManager();
        $projects = $em->getRepository(Project::class);
        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $projectA = $projects->findOneBy(['name' => 'Export statistic A']);
        self::assertInstanceOf(Project::class, $projectA);
        $projectB = $projects->findOneBy(['name' => 'Export statistic B']);
        self::assertInstanceOf(Project::class, $projectB);

        $ownTeam = new Team('Export statistic own team');
        $ownTeam->addTeamlead($teamlead);
        $ownTeam->addUser($this->getUserByRole(User::ROLE_USER));
        $ownTeam->addProject($projectA);
        $em->persist($ownTeam);

        $foreignTeam = new Team('Export statistic foreign team');
        $foreignTeam->addTeamlead($teamlead);
        $foreignTeam->addProject($projectB);
        $em->persist($foreignTeam);

        $em->flush();
        $em->clear();

        $query = $this->createQuery('foo:~');
        $query->setCurrentUser($this->getUserByRole(User::ROLE_USER));

        $preview = $this->createSut()->getPreview($query, 100);

        self::assertEquals(
            ['Export statistic A' => ['count' => 2, 'duration' => 5400, 'rate' => 150.0]],
            $this->normalizeStatistic($preview->getStatistic())
        );
    }

    public function testPreviewLimitsItemsButNotStatistic(): void
    {
        $query = $this->createQuery();
        $query->setMaxResults(1);

        $preview = $this->createSut()->getPreview($query, 2);

        self::assertInstanceOf(ExportablePreviewResult::class, $preview);
        self::assertCount(2, $preview->getExportItems());
        self::assertEquals(
            [
                'Export statistic A' => ['count' => 3, 'duration' => 6000, 'rate' => 160.0],
                'Export statistic B' => ['count' => 2, 'duration' => 1200, 'rate' => 30.0],
            ],
            $this->normalizeStatistic($preview->getStatistic())
        );
        // the given query must not be changed by the preview limit
        self::assertSame(1, $query->getMaxResults());
    }

    public function testPreviewWithoutResults(): void
    {
        $query = $this->createQuery();
        $query->setBegin(new \DateTime('2001-01-01 00:00:00'));
        $query->setEnd(new \DateTime('2001-01-01 23:59:59'));

        $preview = $this->createSut()->getPreview($query, 10);

        self::assertSame([], $preview->getExportItems());
        self::assertSame([], $preview->getStatistic());
    }

    public function testGetResult(): void
    {
        $query = $this->createQuery('foo:~');

        $result = $this->createSut()->getResult($query);

        self::assertCount(3, $result);
        self::assertContainsOnlyInstancesOf(Timesheet::class, $result);
        self::assertTrue($query->hasQueryHint(TimesheetQueryHint::CUSTOMER_META_FIELDS));
        self::assertTrue($query->hasQueryHint(TimesheetQueryHint::PROJECT_META_FIELDS));
        self::assertTrue($query->hasQueryHint(TimesheetQueryHint::ACTIVITY_META_FIELDS));
        self::assertTrue($query->hasQueryHint(TimesheetQueryHint::USER_PREFERENCES));
    }

    public function testGetExportItemsForQuery(): void
    {
        $result = $this->createSut()->getExportItemsForQuery($this->createQuery('foo:~'));

        self::assertIsArray($result);
        self::assertCount(3, $result);
    }
}
