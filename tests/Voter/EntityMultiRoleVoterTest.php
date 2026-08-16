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
use App\Security\RolePermissionManager;
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

    private function assertVote(User $user, mixed $subject, string $attribute, int $result, string $message, ?RolePermissionManager $manager = null): void
    {
        $token = new UsernamePasswordToken($user, 'foo', $user->getRoles());
        $sut = $manager === null ? $this->getVoter(EntityMultiRoleVoter::class) : new EntityMultiRoleVoter($manager);

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
        $allSubjects = ['project', 'customer', new Project(), new Customer('foo')];

        foreach ($allPermissions as $permission) {
            foreach ($allSubjects as $subject) {
                yield [$user3, $subject, $permission, $result];
                yield [$user4, $subject, $permission, $result];
            }
        }

        $result = VoterInterface::ACCESS_GRANTED;
        $allPermissions = ['budget_money', 'budget_time', 'budget_any'];
        $allSubjects = ['activity', new Activity()];

        foreach ($allPermissions as $permission) {
            foreach ($allSubjects as $subject) {
                yield [$user3, $subject, $permission, $result];
                yield [$user4, $subject, $permission, $result];
            }
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
     * A team based permission (e.g. "budget_teamlead_project") used to grant access to every
     * object of that type, no matter whether the user was related to it. Now the user has to be
     * part of the teams which are assigned to the object.
     */
    public function testTeamPermissionIsDeniedForForeignObject(): void
    {
        $user = self::getUser(2, User::ROLE_TEAMLEAD);

        $foreign = new Team('foreign');
        $foreign->addTeamlead(self::getUser(9, User::ROLE_TEAMLEAD));

        $customer = new Customer('foo');
        $foreign->addCustomer($customer);

        $project = new Project();
        $foreign->addProject($project);

        $activity = new Activity();
        $foreign->addActivity($activity);

        $result = VoterInterface::ACCESS_DENIED;
        $message = 'the user is not part of the objects team';

        foreach (['budget_money', 'budget_time', 'budget_any'] as $attribute) {
            $this->assertVote($user, $customer, $attribute, $result, $message);
            $this->assertVote($user, $project, $attribute, $result, $message);
            $this->assertVote($user, $activity, $attribute, $result, $message);
        }

        // the activity is not listed here: a teamlead owns the global "view_activity" permission,
        // which is not team based and therefore grants access before any team is inspected
        $this->assertVote($user, $customer, 'listing', $result, $message);
        $this->assertVote($user, $project, 'listing', $result, $message);
    }

    public function testTeamPermissionIsGrantedForOwnObject(): void
    {
        $user = self::getUser(2, User::ROLE_TEAMLEAD);

        $team = new Team('own');
        $team->addTeamlead($user);

        $customer = new Customer('foo');
        $team->addCustomer($customer);

        $project = new Project();
        $team->addProject($project);

        $activity = new Activity();
        $team->addActivity($activity);

        $result = VoterInterface::ACCESS_GRANTED;
        $message = 'the user is part of the objects team';

        foreach (['budget_money', 'budget_time', 'budget_any'] as $attribute) {
            $this->assertVote($user, $customer, $attribute, $result, $message);
            $this->assertVote($user, $project, $attribute, $result, $message);
            $this->assertVote($user, $activity, $attribute, $result, $message);
        }

        $this->assertVote($user, $customer, 'listing', $result, $message);
        $this->assertVote($user, $project, 'listing', $result, $message);
    }

    /**
     * The "details" attribute only knows the global permission in this voter, the team based
     * variant ("details_teamlead_project") is answered by the Project- and CustomerVoter.
     */
    public function testDetailsOnlyKnowsTheGlobalPermission(): void
    {
        $teamlead = self::getUser(2, User::ROLE_TEAMLEAD);
        $admin = self::getUser(3, User::ROLE_ADMIN);

        $team = new Team('own');
        $team->addTeamlead($teamlead);

        $customer = new Customer('foo');
        $team->addCustomer($customer);

        $project = new Project();
        $team->addProject($project);

        $this->assertVote($admin, $customer, 'details', VoterInterface::ACCESS_GRANTED, 'the admin owns the global permission');
        $this->assertVote($admin, $project, 'details', VoterInterface::ACCESS_GRANTED, 'the admin owns the global permission');

        $this->assertVote($teamlead, $customer, 'details', VoterInterface::ACCESS_DENIED, 'the teamlead is granted by the CustomerVoter instead');
        $this->assertVote($teamlead, $project, 'details', VoterInterface::ACCESS_DENIED, 'the teamlead is granted by the ProjectVoter instead');
    }

    /**
     * Objects without any team are visible for everyone with a team based permission,
     * this is the documented behaviour of RolePermissionManager::checkTeamAccess().
     */
    public function testTeamPermissionIsGrantedForObjectWithoutTeam(): void
    {
        $user = self::getUser(2, User::ROLE_TEAMLEAD);

        $result = VoterInterface::ACCESS_GRANTED;
        $message = 'the object is not restricted by any team';

        foreach (['budget_money', 'budget_time', 'budget_any'] as $attribute) {
            $this->assertVote($user, new Customer('foo'), $attribute, $result, $message);
            $this->assertVote($user, new Project(), $attribute, $result, $message);
            $this->assertVote($user, new Activity(), $attribute, $result, $message);
        }
    }

    /**
     * The string subject asks "may this user see that kind of data on any object?" and cannot
     * be team checked. It is used to decide whether a column or menu is rendered at all.
     */
    public function testStringSubjectIsNotTeamChecked(): void
    {
        $user = self::getUser(2, User::ROLE_TEAMLEAD);

        $result = VoterInterface::ACCESS_GRANTED;
        $message = 'a string subject is not related to a team';

        foreach (['budget_money', 'budget_time', 'budget_any'] as $attribute) {
            $this->assertVote($user, 'customer', $attribute, $result, $message);
            $this->assertVote($user, 'project', $attribute, $result, $message);
            $this->assertVote($user, 'activity', $attribute, $result, $message);
        }
    }

    /**
     * A permission which is not team based (e.g. "budget_project") is unaffected by the teams.
     */
    public function testGlobalPermissionIgnoresTeams(): void
    {
        $user = self::getUser(3, User::ROLE_ADMIN);

        $foreign = new Team('foreign');
        $foreign->addTeamlead(self::getUser(9, User::ROLE_TEAMLEAD));

        $customer = new Customer('foo');
        $foreign->addCustomer($customer);

        $project = new Project();
        $foreign->addProject($project);

        $activity = new Activity();
        $foreign->addActivity($activity);

        $result = VoterInterface::ACCESS_GRANTED;
        $message = 'the permission is not team based';

        foreach (['budget_money', 'budget_time', 'budget_any'] as $attribute) {
            $this->assertVote($user, $customer, $attribute, $result, $message);
            $this->assertVote($user, $project, $attribute, $result, $message);
            $this->assertVote($user, $activity, $attribute, $result, $message);
        }
    }

    /**
     * The team of a customer is inherited by its projects and their activities.
     */
    public function testTeamPermissionRespectsObjectHierarchy(): void
    {
        $user = self::getUser(2, User::ROLE_TEAMLEAD);

        $own = new Team('own');
        $own->addTeamlead($user);

        $foreign = new Team('foreign');
        $foreign->addTeamlead(self::getUser(9, User::ROLE_TEAMLEAD));

        $customer = new Customer('foo');
        $foreign->addCustomer($customer);

        $project = new Project();
        $project->setCustomer($customer);
        $own->addProject($project);

        $activity = new Activity();
        $activity->setProject($project);
        $own->addActivity($activity);

        $result = VoterInterface::ACCESS_DENIED;
        $message = 'the user is not part of the customers team';

        foreach (['budget_money', 'budget_time', 'budget_any'] as $attribute) {
            $this->assertVote($user, $project, $attribute, $result, $message);
            $this->assertVote($user, $activity, $attribute, $result, $message);
        }
    }

    /**
     * The team check may not swallow the remaining permissions: "budget_any" is granted as soon
     * as one of the money or time permissions matches.
     */
    public function testTeamPermissionDoesNotHideGlobalPermission(): void
    {
        $user = self::getUser(1, User::ROLE_USER);

        $foreign = new Team('foreign');
        $foreign->addTeamlead(self::getUser(9, User::ROLE_TEAMLEAD));

        $project = new Project();
        $foreign->addProject($project);

        // this role may see the time budget of every project, but never the money budget
        $manager = $this->getRolePermissionManager(['ROLE_USER' => ['time_project']], true);

        $this->assertVote($user, $project, 'budget_any', VoterInterface::ACCESS_GRANTED, 'the global time permission is not team based', $manager);
        $this->assertVote($user, $project, 'budget_time', VoterInterface::ACCESS_GRANTED, 'the global time permission is not team based', $manager);
        $this->assertVote($user, $project, 'budget_money', VoterInterface::ACCESS_DENIED, 'the role has no money permission', $manager);
    }
}
