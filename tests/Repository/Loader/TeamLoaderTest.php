<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Loader;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Repository\Loader\TeamLoader;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TeamLoader::class)]
class TeamLoaderTest extends AbstractLoaderTestCase
{
    public function testLoadResults(): void
    {
        $em = $this->getEntityManagerMock(2);

        $sut = new TeamLoader($em);

        $entity = $this->createMock(Team::class);
        $entity->expects($this->once())->method('getId')->willReturn(1);

        $sut->loadResults([$entity]);
    }

    public function testLoadResultsWithEmptyResults(): void
    {
        $em = $this->getEntityManagerMock(0);

        $sut = new TeamLoader($em, true, true);
        $sut->loadResults([]);
    }

    /**
     * @return Team[]
     */
    private function createTeamsWithMembers(): array
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(10);
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(11);

        $member1 = $this->createMock(TeamMember::class);
        $member1->method('getUser')->willReturn($user1);
        $member2 = $this->createMock(TeamMember::class);
        $member2->method('getUser')->willReturn($user2);
        // the same user in two teams must only be loaded once
        $member3 = $this->createMock(TeamMember::class);
        $member3->method('getUser')->willReturn($user1);
        $member4 = $this->createMock(TeamMember::class);
        $member4->method('getUser')->willReturn(null);

        $team1 = $this->createMock(Team::class);
        $team1->method('getId')->willReturn(1);
        $team1->method('getMembers')->willReturn(new ArrayCollection([$member1, $member2]));
        $team2 = $this->createMock(Team::class);
        $team2->method('getId')->willReturn(2);
        $team2->method('getMembers')->willReturn(new ArrayCollection([$member3, $member4]));

        return [$team1, $team2];
    }

    public function testLoadResultsDoesNotLoadUserPreferencesByDefault(): void
    {
        $teams = $this->createTeamsWithMembers();
        // teams + members/users, teams + projects
        $em = $this->getEntityManagerMock(2, $teams);

        $sut = new TeamLoader($em);
        $sut->loadResults($teams);
    }

    public function testLoadResultsWithUserPreferences(): void
    {
        $teams = $this->createTeamsWithMembers();
        // teams + members/users, users + preferences, teams + projects
        $em = $this->getEntityManagerMock(3, $teams);

        $sut = new TeamLoader($em, false, true);
        $sut->loadResults($teams);
    }

    public function testLoadResultsWithUserPreferencesAndWithoutMembers(): void
    {
        $team = $this->createMock(Team::class);
        $team->method('getId')->willReturn(1);
        $team->method('getMembers')->willReturn(new ArrayCollection([]));

        // no members => no user query
        $em = $this->getEntityManagerMock(2, [$team]);

        $sut = new TeamLoader($em, false, true);
        $sut->loadResults([$team]);
    }
}
