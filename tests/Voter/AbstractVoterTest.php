<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\User;
use App\Security\AclDecisionManager;
use App\Security\RolePermissionManager;
use App\Voter\AbstractVoter;
use PHPUnit\Framework\TestCase;

abstract class AbstractVoterTest extends TestCase
{
    /**
     * @param string $voterClass
     * @param User $user
     * @return AbstractVoter
     * @throws \ReflectionException
     */
    protected function getVoter(string $voterClass, User $user)
    {
        $isAuthenticated = empty($user->getRoles());
        $accessManager = $this->getMockBuilder(AclDecisionManager::class)->disableOriginalConstructor()->getMock();
        $accessManager->method('isFullyAuthenticated')->willReturn($isAuthenticated);

        $class = new \ReflectionClass($voterClass);
        /** @var AbstractVoter $voter */
        $voter = $class->newInstance($accessManager, $this->getRolePermissionManager());
        self::assertInstanceOf(AbstractVoter::class, $voter);

        return $voter;
    }

    /**
     * @param int $id
     * @param string $role
     * @return User
     */
    protected function getUser($id, $role)
    {
        $roles = [];
        if (!empty($role)) {
            $roles[] = $role;
        }

        $user = $this->getMockBuilder(User::class)->getMock();
        $user->method('getId')->willReturn($id);
        $user->method('getRoles')->willReturn($roles);

        return $user;
    }

    /**
     * @param array $permissions
     * @param bool $overwrite
     * @return RolePermissionManager
     */
    protected function getRolePermissionManager(array $permissions = [], bool $overwrite = false)
    {
        if (!$overwrite) {
            $activities = ['view_activity', 'edit_activity', 'budget_activity', 'delete_activity', 'create_activity'];
            $projects = ['view_project', 'edit_project', 'budget_project', 'delete_project', 'create_project'];
            $customers = ['view_customer', 'edit_customer', 'budget_customer', 'delete_customer', 'create_customer'];
            $invoice = ['view_invoice', 'create_invoice'];
            $invoiceTemplate = ['view_invoice_template', 'create_invoice_template', 'edit_invoice_template', 'delete_invoice_template'];
            $timesheet = ['view_own_timesheet', 'start_own_timesheet', 'stop_own_timesheet', 'create_own_timesheet', 'edit_own_timesheet', 'export_own_timesheet', 'delete_own_timesheet'];
            $timesheetOthers = ['view_other_timesheet', 'start_other_timesheet', 'stop_other_timesheet', 'create_other_timesheet', 'edit_other_timesheet',  'export_other_timesheet', 'delete_other_timesheet'];
            $profile = ['view_own_profile', 'edit_own_profile', 'password_own_profile', 'preferences_own_profile', 'api-token_own_profile'];
            $profileOther = ['view_other_profile', 'edit_other_profile', 'delete_other_profile', 'password_other_profile', 'roles_other_profile', 'preferences_other_profile', 'api-token_other_profile'];
            $user = ['view_user', 'create_user', 'delete_user'];
            $rate = ['view_rate_own_timesheet', 'edit_rate_own_timesheet'];
            $rateOther = ['view_rate_other_timesheet', 'edit_rate_other_timesheet'];

            $roleUser = [];
            $roleTeamlead = ['view_rate_own_timesheet', 'view_rate_other_timesheet', 'hourly-rate_own_profile'];
            $roleAdmin = ['hourly-rate_own_profile', 'edit_exported_timesheet'];
            $roleSuperAdmin = ['hourly-rate_own_profile', 'hourly-rate_other_profile', 'delete_own_profile', 'roles_own_profile', 'system_information', 'system_configuration', 'plugins', 'edit_exported_timesheet'];

            $permissions = [
                'ROLE_USER' => array_merge($timesheet, $profile, $roleUser),
                'ROLE_TEAMLEAD' => array_merge($invoice, $timesheet, $timesheetOthers, $profile, $roleTeamlead),
                'ROLE_ADMIN' => array_merge($activities, $projects, $customers, $invoice, $invoiceTemplate, $timesheet, $timesheetOthers, $profile, $rate, $rateOther, $roleAdmin),
                'ROLE_SUPER_ADMIN' => array_merge($activities, $projects, $customers, $invoice, $invoiceTemplate, $timesheet, $timesheetOthers, $profile, $profileOther, $user, $rate, $rateOther, $roleSuperAdmin),
            ];
        }

        return new RolePermissionManager($permissions);
    }
}
