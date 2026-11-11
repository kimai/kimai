<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectFormTypeQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(ProjectRepository::class)]
#[Group('integration')]
class ProjectRepositoryTest extends AbstractRepositoryTestCase
{
    private function createProject(\DateTimeImmutable $lockedUntil, string $suffix): int
    {
        $em = $this->getEntityManager();

        $customer = new Customer('lock customer ' . $suffix);
        $customer->setCountry('DE');
        $customer->setCurrency('EUR');
        $customer->setTimezone(date_default_timezone_get());
        $em->persist($customer);

        $project = new Project();
        $project->setName('lock project ' . $suffix);
        $project->setCustomer($customer);
        $project->setLockedUntil($lockedUntil);
        $em->persist($project);
        $em->flush();

        $id = $project->getId();
        self::assertIsInt($id);

        return $id;
    }

    private function createTeamProject(string $name, ?Team $team): void
    {
        $em = $this->getEntityManager();

        $customer = new Customer('preselect customer ' . $name);
        $customer->setCountry('DE');
        $customer->setCurrency('EUR');
        $customer->setTimezone(date_default_timezone_get());
        $em->persist($customer);

        $project = new Project();
        $project->setName('preselect project ' . $name);
        $project->setCustomer($customer);
        $project->setVisible(false);
        $em->persist($project);

        $team?->addProject($project);
    }

    private function getProjectId(string $name): int
    {
        $project = $this->getEntityManager()->getRepository(Project::class)->findOneBy(['name' => $name]);
        self::assertInstanceOf(Project::class, $project);
        $id = $project->getId();
        self::assertIsInt($id);

        return $id;
    }

    /**
     * @return array<int|null>
     */
    private function getFormTypeProjectIds(ProjectFormTypeQuery $query): array
    {
        $repository = $this->getEntityManager()->getRepository(Project::class);
        self::assertInstanceOf(ProjectRepository::class, $repository);

        /** @var array<Project> $projects */
        $projects = $repository->getQueryBuilderForFormType($query)->getQuery()->getResult();

        return array_map(function (Project $project) {
            return $project->getId();
        }, $projects);
    }

    /**
     * A requested project ID may skip the visibility filter, but never the team permissions.
     */
    public function testFormTypeQueryChecksTeamPermissionsForRequestedProjects(): void
    {
        $em = $this->getEntityManager();

        $own = new Team('preselect own team');
        $own->addUser($this->getUserByRole(User::ROLE_USER));
        $em->persist($own);
        $foreign = new Team('preselect foreign team');
        $em->persist($foreign);

        $this->createTeamProject('foreign', $foreign);
        $this->createTeamProject('own', $own);
        $em->flush();
        $em->clear();

        $foreignId = $this->getProjectId('preselect project foreign');
        $ownId = $this->getProjectId('preselect project own');
        $user = $this->getUserByRole(User::ROLE_USER);

        // IDs as they are taken from a request
        $query = new ProjectFormTypeQuery();
        $query->setProjects([$foreignId, $ownId]);
        $query->setUser($user);
        $ids = $this->getFormTypeProjectIds($query);
        self::assertNotContains($foreignId, $ids, 'A project of a foreign team was added by its ID.');
        self::assertContains($ownId, $ids, 'An invisible project of the own team can still be requested by its ID.');

        // the opt-in, which is used for the project of an already stored record
        $query = new ProjectFormTypeQuery();
        $query->setProjects([$foreignId]);
        $query->setUser($user);
        $query->setAllowProjectPreselect(true);
        self::assertContains($foreignId, $this->getFormTypeProjectIds($query));

        // users who can see all data are not restricted
        $query = new ProjectFormTypeQuery();
        $query->setProjects([$foreignId]);
        $query->setUser($this->getUserByRole(User::ROLE_SUPER_ADMIN));
        self::assertContains($foreignId, $this->getFormTypeProjectIds($query));
    }

    /**
     * @return \Generator<array{0: string, 1: string}>
     */
    public static function getTimezones(): \Generator
    {
        yield ['UTC', '00:00:00'];
        yield ['Europe/Vienna', '23:59:59'];
        yield ['Europe/Vienna', '00:00:00'];
        yield ['Pacific/Tahiti', '23:59:59'];
        yield ['Pacific/Tahiti', '00:00:00'];
        yield ['Pacific/Kiritimati', '23:59:59'];
        yield ['Asia/Tokyo', '12:00:00'];
    }

    /**
     * The lock date is a calendar day: neither the time nor the timezone that is handed over
     * may move it to another day, no matter which timezone the reading user works in.
     */
    #[DataProvider('getTimezones')]
    public function testLockedUntilIsStoredAsPlainDay(string $timezone, string $time): void
    {
        $default = date_default_timezone_get();

        try {
            date_default_timezone_set($timezone);
            $id = $this->createProject(
                new \DateTimeImmutable('2026-08-06 ' . $time, new \DateTimeZone($timezone)),
                $timezone . $time
            );

            $em = $this->getEntityManager();

            // the raw database value must be the plain day
            $raw = $em->getConnection()->fetchOne('SELECT locked_until FROM kimai2_projects WHERE id = ?', [$id]);
            self::assertEquals('2026-08-06', $raw);

            // and it must read back as the same day from every timezone
            foreach (['UTC', 'Europe/Vienna', 'Pacific/Tahiti', 'Pacific/Kiritimati'] as $readerTimezone) {
                $em->clear();
                date_default_timezone_set($readerTimezone);

                $project = $em->getRepository(Project::class)->find($id);
                self::assertInstanceOf(Project::class, $project);
                self::assertEquals('2026-08-06', $project->getLockedUntil()?->format('Y-m-d'), $readerTimezone);

                $zone = new \DateTimeZone($readerTimezone);
                self::assertTrue($project->isLockedAtDate(new \DateTime('2026-08-06 23:00:00', $zone)), $readerTimezone);
                self::assertFalse($project->isLockedAtDate(new \DateTime('2026-08-07 00:00:00', $zone)), $readerTimezone);
            }
        } finally {
            date_default_timezone_set($default);
        }
    }
}
