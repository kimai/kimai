<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Voter\EntityMultiRoleVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(EntityMultiRoleVoter::class)]
class EntityMultiRoleVoterTest extends AbstractVoterTestCase
{
    #[DataProvider('getTestData')]
    public function testVote(User $user, $subject, $attribute, $result): void
    {
        $token = new UsernamePasswordToken($user, 'foo', $user->getRoles());
        $sut = $this->getVoter(EntityMultiRoleVoter::class);

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]), 'Failed on permission "' . $attribute . '" for User ' . $user->getUserIdentifier());
    }

    private function assertVote(User $user, mixed $subject, string $attribute, int $result, string $message): void
    {
        $token = new UsernamePasswordToken($user, 'foo', $user->getRoles());
        $sut = $this->getVoter(EntityMultiRoleVoter::class);

        self::assertEquals($result, $sut->vote($token, $subject, [$attribute]), \sprintf('Failed on permission "%s": %s', $attribute, $message));
    }

    public static function getTestData()
    {
        $user0 = self::getUser(0, null);
        $user1 = self::getUser(1, User::ROLE_USER);
        $user2 = self::getUser(2, User::ROLE_TEAMLEAD);
        $user3 = self::getUser(3, User::ROLE_ADMIN);
        $user4 = self::getUser(4, User::ROLE_SUPER_ADMIN);

        $result = VoterInterface::ACCESS_GRANTED;
        $allPermissions = ['budget_money', 'budget_time', 'budget_any', 'details'];

        foreach ($allPermissions as $permission) {
            foreach (['project', 'customer'] as $subject) {
                yield [$user3, $subject, $permission, $result];
                yield [$user4, $subject, $permission, $result];
            }
        }

        $result = VoterInterface::ACCESS_GRANTED;
        $allPermissions = ['budget_money', 'budget_time', 'budget_any'];

        foreach ($allPermissions as $permission) {
            yield [$user3, 'activity', $permission, $result];
            yield [$user4, 'activity', $permission, $result];
        }

        $result = VoterInterface::ACCESS_DENIED;
        yield [$user4, 'activity', 'details', $result]; // there is no details permission for activity

        $result = VoterInterface::ACCESS_ABSTAIN;
        yield [$user0, 'team', 'view', $result];
        yield [$user0, 'team', 'edit', $result];
        yield [$user0, 'team', 'delete', $result];
        yield [$user1, 'team', 'view', $result];
        yield [$user1, 'team', 'edit', $result];
        yield [$user1, 'team', 'delete', $result];
        yield [$user2, 'team', 'view', $result];
        yield [$user2, 'team', 'edit', $result];
        yield [$user2, 'team', 'delete', $result];
        yield [$user3, 'team', 'view', $result];
        yield [$user3, 'team', 'edit', $result];
        yield [$user3, 'team', 'delete', $result];
        yield [$user4, 'team', 'view', $result];
        yield [$user4, 'team', 'edit', $result];
        yield [$user4, 'team', 'delete', $result];
    }

    /**
     * This voter answers for the entity type only. A concrete object has to be checked with the
     * CustomerVoter, ProjectVoter or ActivityVoter, which are the only ones knowing the teams of
     * that object - answering here would mean answering a question this voter cannot decide.
     */
    public function testObjectSubjectIsNotSupported(): void
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        $message = 'a concrete object is not supported';

        $team = new Team('own');

        $customer = new Customer('foo');
        $team->addCustomer($customer);

        $project = new Project();
        $team->addProject($project);

        $activity = new Activity();
        $team->addActivity($activity);

        foreach ([User::ROLE_USER, User::ROLE_TEAMLEAD, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN] as $i => $role) {
            $user = self::getUser($i, $role);
            $team->addTeamlead($user);

            foreach (['budget_money', 'budget_time', 'budget_any', 'details', 'listing'] as $attribute) {
                $this->assertVote($user, $customer, $attribute, $result, $message);
                $this->assertVote($user, $project, $attribute, $result, $message);
                $this->assertVote($user, $activity, $attribute, $result, $message);
            }
        }
    }

    public function testSupportsType(): void
    {
        $sut = new EntityMultiRoleVoter($this->getRolePermissionManager());

        self::assertTrue($sut->supportsType('string'));
        self::assertFalse($sut->supportsType(Project::class));
        self::assertFalse($sut->supportsType(Customer::class));
        self::assertFalse($sut->supportsType(Activity::class));
    }

    /**
     * The permissions are evaluated for the entity type, so a team based permission is enough:
     * the question is whether there could be any object the user may see that data for.
     */
    public function testTeamPermissionGrantsOnEntityType(): void
    {
        $user = self::getUser(2, User::ROLE_TEAMLEAD);

        $result = VoterInterface::ACCESS_GRANTED;
        $message = 'the teamlead owns the team based permission';

        foreach (['budget_money', 'budget_time', 'budget_any'] as $attribute) {
            $this->assertVote($user, 'customer', $attribute, $result, $message);
            $this->assertVote($user, 'project', $attribute, $result, $message);
            $this->assertVote($user, 'activity', $attribute, $result, $message);
        }

        $this->assertVote($user, 'customer', 'listing', $result, $message);
        $this->assertVote($user, 'project', 'listing', $result, $message);
        $this->assertVote($user, 'activity', 'listing', $result, $message);
    }

    /**
     * A role without any of the permissions is denied, which is what hides the columns.
     */
    public function testWithoutPermission(): void
    {
        $user = self::getUser(1, User::ROLE_USER);

        $result = VoterInterface::ACCESS_DENIED;
        $message = 'the role owns none of the permissions';

        foreach (['budget_money', 'budget_any', 'details'] as $attribute) {
            $this->assertVote($user, 'customer', $attribute, $result, $message);
            $this->assertVote($user, 'project', $attribute, $result, $message);
        }
    }
}
