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

    /**
     * @return array{own: Project, hidden: Project, foreign: Project, foreignCustomer: Project}
     */
    private function createTeamProjects(User $user): array
    {
        $em = $this->getEntityManager();

        $ownTeam = new Team('form type own team');
        $ownTeam->addUser($user);
        $em->persist($ownTeam);

        $foreignTeam = new Team('form type foreign team');
        $em->persist($foreignTeam);

        $openCustomer = new Customer('form type open customer');
        $openCustomer->setCountry('DE');
        $openCustomer->setTimezone('Europe/Berlin');
        $em->persist($openCustomer);

        $foreignCustomer = new Customer('form type foreign customer');
        $foreignCustomer->setCountry('DE');
        $foreignCustomer->setTimezone('Europe/Berlin');
        $foreignTeam->addCustomer($foreignCustomer);
        $em->persist($foreignCustomer);

        $own = new Project();
        $own->setName('form type own project');
        $own->setCustomer($openCustomer);
        $ownTeam->addProject($own);
        $em->persist($own);

        $hidden = new Project();
        $hidden->setName('form type hidden own project');
        $hidden->setCustomer($openCustomer);
        $hidden->setVisible(false);
        $ownTeam->addProject($hidden);
        $em->persist($hidden);

        // restricted on project level
        $foreign = new Project();
        $foreign->setName('form type foreign project');
        $foreign->setCustomer($openCustomer);
        $foreignTeam->addProject($foreign);
        $em->persist($foreign);

        // unrestricted project, but restricted on customer level
        $foreignCustomerProject = new Project();
        $foreignCustomerProject->setName('form type project of foreign customer');
        $foreignCustomerProject->setCustomer($foreignCustomer);
        $em->persist($foreignCustomerProject);

        $em->flush();

        return ['own' => $own, 'hidden' => $hidden, 'foreign' => $foreign, 'foreignCustomer' => $foreignCustomerProject];
    }

    /**
     * @return array<int|null>
     */
    private function findProjectIdsForFormType(ProjectFormTypeQuery $query): array
    {
        /** @var ProjectRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Project::class);

        return array_map(fn (Project $project) => $project->getId(), $repository->getQueryBuilderForFormType($query)->getQuery()->getResult());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function getForeignProjects(): \Generator
    {
        yield 'restricted project' => ['foreign'];
        yield 'project of restricted customer' => ['foreignCustomer'];
    }

    /**
     * IDs passed to the "include" option must not bypass the team permission criteria.
     * The IDs are passed as integers, as the toolbar forms do with the submitted request data.
     */
    #[DataProvider('getForeignProjects')]
    public function testFormTypeQueryDoesNotIncludeProjectsOfForeignTeam(string $key): void
    {
        $user = $this->getUserByRole(User::ROLE_USER);
        $projects = $this->createTeamProjects($user);

        $query = new ProjectFormTypeQuery((int) $projects[$key]->getId());
        $query->setUser($user);
        $query->setIgnoreDate(true);

        $ids = $this->findProjectIdsForFormType($query);

        self::assertContains($projects['own']->getId(), $ids);
        self::assertNotContains($projects[$key]->getId(), $ids, 'Project of a foreign team was included');
    }

    /**
     * Guard for the intended use of the "include" option: allow a hidden (but accessible) project, e.g. for existing records.
     */
    public function testFormTypeQueryIncludesHiddenProjectOfOwnTeam(): void
    {
        $user = $this->getUserByRole(User::ROLE_USER);
        $projects = $this->createTeamProjects($user);

        $query = new ProjectFormTypeQuery();
        $query->setUser($user);
        $query->setIgnoreDate(true);
        self::assertNotContains($projects['hidden']->getId(), $this->findProjectIdsForFormType($query));

        $query->addProject((int) $projects['hidden']->getId());
        self::assertContains($projects['hidden']->getId(), $this->findProjectIdsForFormType($query));
    }
}
