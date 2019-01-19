<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\Activity;
use App\Entity\User;
use App\Voter\RolePermissionVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Voter\RolePermissionVoter
 */
class RolePermissionVoterTest extends AbstractVoterTest
{
    /**
     * @dataProvider getTestData
     */
    public function testVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(RolePermissionVoter::class, $user);

        $this->assertEquals($result, $sut->vote($token, $subject, [$attribute]));
    }

    public function getTestData()
    {
        $user0 = $this->getUser(0, null);
        $user1 = $this->getUser(1, User::ROLE_USER);
        $user2 = $this->getUser(2, User::ROLE_TEAMLEAD);
        $user3 = $this->getUser(3, User::ROLE_ADMIN);
        $user4 = $this->getUser(4, User::ROLE_SUPER_ADMIN);

        $invoice = [
            'create_invoice_template' => null,
            'view_invoice' => null,
            'create_invoice' => null,
        ];

        $timesheet = [
            'view_own_timesheet' => null,
            'create_own_timesheet' => null,
            'export_own_timesheet' => null,
        ];

        $timesheetOther = [
            'view_other_timesheet' => null,
            'create_other_timesheet' => null,
        ];

        $others = [
            'create_activity' => null,
            'create_customer' => null,
            'create_project' => null,
        ];

        $users = [
            'create_user' => null,
            'view_user' => null,
        ];

        // ================== GRANTED ==================
        $result = VoterInterface::ACCESS_GRANTED;

        $entries = array_merge($timesheet);
        foreach ([$user1, $user2, $user3, $user4] as $user) {
            foreach ($entries as $permission => $entity) {
                yield [$user, $entity, $permission, $result];
                yield [$user, null, $permission, $result];
            }
        }

        $entriesAdmin = array_merge($others, $timesheetOther, $invoice, $timesheet);
        foreach ([$user3, $user4] as $user) {
            foreach ($entriesAdmin as $permission => $entity) {
                yield [$user, $entity, $permission, $result];
                yield [$user, null, $permission, $result];
            }
        }

        $entriesSuperAdmin = array_merge($users);
        foreach ($entriesSuperAdmin as $permission => $entity) {
            yield [$user4, $entity, $permission, $result];
            yield [$user4, null, $permission, $result];
        }

        // ================== DENIED ==================
        // this test might fail in the future due to the role permissions
        $result = VoterInterface::ACCESS_DENIED;

        foreach ([$user0, $user1, $user2] as $user) {
            foreach ($others as $permission => $entity) {
                yield [$user, $entity, $permission, $result];
            }
        }
        foreach ([$user0, $user1] as $user) {
            foreach ($invoice as $permission => $entity) {
                yield [$user, $entity, $permission, $result];
            }
        }
        foreach ([$user0] as $user) {
            foreach ($timesheet as $permission => $entity) {
                yield [$user, $entity, $permission, $result];
            }
        }
        foreach ([$user0, $user1] as $user) {
            foreach ($timesheetOther as $permission => $entity) {
                yield [$user, $entity, $permission, $result];
            }
        }

        // ================== ABSTAIN ==================
        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ([$user3, $user4] as $user) {
            yield [$user, new Activity(), 'view', $result];
            yield [$user, new Activity(), 'edit', $result];
            yield [$user, new Activity(), 'delete', $result];
            yield [$user, new Activity(), 'ROLE_USER', $result];
            yield [$user, new Activity(), 'ROLE_ADMIN', $result];
            yield [$user, new \stdClass(), 'view', $result];
            yield [$user, null, 'edit', $result];
            yield [$user, $user, 'delete', $result];
        }
    }
}
