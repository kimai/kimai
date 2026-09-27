<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\TeamRepository;
use Doctrine\ORM\PersistentCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(TeamRepository::class)]
#[Group('integration')]
class TeamRepositoryTest extends AbstractRepositoryTestCase
{
    private function getRepository(): TeamRepository
    {
        $repository = $this->getEntityManager()->getRepository(Team::class);
        self::assertInstanceOf(TeamRepository::class, $repository);

        return $repository;
    }

    private function createUser(string $username): User
    {
        $user = new User();
        $user->setUserIdentifier($username);
        $user->setEmail($username . '@example.com');
        $user->setPassword('foo');
        $user->addPreference(new UserPreference(UserPreference::LOCALE, 'de'));
        $user->addPreference(new UserPreference(UserPreference::TIMEZONE, 'Europe/Berlin'));

        $this->getEntityManager()->persist($user);

        return $user;
    }

    private function createTeamWithMembers(int $memberCount): int
    {
        $team = new Team('loader-test-' . uniqid());
        for ($i = 0; $i < $memberCount; $i++) {
            $team->addUser($this->createUser('team-loader-' . uniqid()));
        }
        $this->getRepository()->saveTeam($team);
        $id = $team->getId();
        self::assertNotNull($id);

        // simulate a fresh request (e.g. an API call), where the team is hydrated from the database
        $this->getEntityManager()->clear();

        return $id;
    }

    /**
     * Resolves the team the same way #[MapEntity] does in the API controllers.
     */
    private function findTeam(int $id): Team
    {
        $team = $this->getRepository()->find($id);
        self::assertInstanceOf(Team::class, $team);

        return $team;
    }

    private function assertMembersAreFullyHydrated(Team $team, int $expectedMembers, bool $expectPreferences): void
    {
        $uow = $this->getEntityManager()->getUnitOfWork();

        $members = $team->getMembers();
        self::assertInstanceOf(PersistentCollection::class, $members);
        self::assertTrue($members->isInitialized(), 'Team members were not preloaded');
        self::assertCount($expectedMembers, $members);

        foreach ($members as $member) {
            $user = $member->getUser();
            self::assertInstanceOf(User::class, $user);
            self::assertFalse($uow->isUninitializedObject($user), 'Member user was not preloaded');

            $preferences = $user->getPreferences();
            self::assertInstanceOf(PersistentCollection::class, $preferences);
            self::assertSame($expectPreferences, $preferences->isInitialized(), 'User preferences were ' . ($expectPreferences ? 'not ' : '') . 'preloaded');
        }
    }

    public function testFindDoesNotPreloadMembers(): void
    {
        $id = $this->createTeamWithMembers(2);
        $team = $this->findTeam($id);

        $members = $team->getMembers();
        self::assertInstanceOf(PersistentCollection::class, $members);
        self::assertFalse($members->isInitialized());
    }

    public function testCreateTeamLoaderPreloadsMembersUsersAndPreferences(): void
    {
        $id = $this->createTeamWithMembers(3);
        $team = $this->findTeam($id);

        $loader = $this->getRepository()->createTeamLoader(false, true);
        $loader->loadResults([$team]);

        $this->assertMembersAreFullyHydrated($team, 3, true);

        // the virtual API properties must be served from the preloaded preferences
        foreach ($team->getMembers() as $member) {
            $user = $member->getUser();
            self::assertInstanceOf(User::class, $user);
            self::assertEquals('de', $user->getLocale());
            self::assertEquals('Europe/Berlin', $user->getTimezone());
        }
    }

    public function testCreateTeamLoaderWithoutMembers(): void
    {
        $id = $this->createTeamWithMembers(0);
        $team = $this->findTeam($id);

        $loader = $this->getRepository()->createTeamLoader(false, true);
        $loader->loadResults([$team]);

        $this->assertMembersAreFullyHydrated($team, 0, true);
    }

    public function testFindByIdsPreloadsMembersButNotPreferences(): void
    {
        $id = $this->createTeamWithMembers(2);

        $teams = $this->getRepository()->findByIds([$id]);

        self::assertCount(1, $teams);
        $this->assertMembersAreFullyHydrated($teams[0], 2, false);
    }
}
