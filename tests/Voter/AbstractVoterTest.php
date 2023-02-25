<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\User;
use App\Repository\RolePermissionRepository;
use App\Security\RolePermissionManager;
use App\User\PermissionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class AbstractVoterTest extends TestCase
{
    protected function getVoter(string $voterClass): Voter
    {
        $class = new \ReflectionClass($voterClass);
        /** @var Voter $voter */
        $voter = $class->newInstance($this->getRolePermissionManager());
        self::assertInstanceOf(Voter::class, $voter);

        return $voter;
    }

    /**
     * @param int $id
     * @param string|null $role
     * @return User
     */
    protected function getUser(int $id, ?string $role)
    {
        $roles = [];
        if (!empty($role)) {
            $roles[] = $role;
        }

        $user = new User();
        $user->setRoles($roles);
        $user->setUserIdentifier((string) $id);

        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, $id);

        return $user;
    }

    /**
     * @param array<string, array<string>> $permissions
     * @param bool $overwrite
     * @return RolePermissionManager
     */
    protected function getRolePermissionManager(array $permissions = [], bool $overwrite = false): RolePermissionManager
    {
        if (!$overwrite) {
            $activities = ['view_activity', 'edit_activity', 'budget_activity', 'time_activity', 'delete_activity', 'create_activity'];
            $activitiesTeam = ['view_activity', 'create_activity', 'edit_teamlead_activity', 'budget_teamlead_activity', 'time_teamlead_activity'];
            $projects = ['view_project', 'create_project', 'edit_project', 'budget_project', 'time_project', 'delete_project', 'permissions_project', 'comments_project', 'details_project'];
            $projectsTeam = ['view_teamlead_project', 'edit_teamlead_project', 'budget_teamlead_project', 'time_teamlead_project', 'permissions_teamlead_project', 'comments_teamlead_project', 'details_teamlead_project'];
            $customers = ['view_customer', 'create_customer', 'edit_customer', 'budget_customer', 'time_customer', 'delete_customer', 'permissions_customer', 'comments_customer', 'details_customer'];
            $customersTeam = ['view_teamlead_customer', 'edit_teamlead_customer', 'budget_teamlead_customer', 'time_teamlead_customer', 'comments_teamlead_customer', 'details_teamlead_customer'];
            $invoice = ['view_invoice', 'create_invoice'];
            $invoiceTemplate = ['manage_invoice_template'];
            $timesheet = ['view_own_timesheet', 'start_own_timesheet', 'stop_own_timesheet', 'create_own_timesheet', 'edit_own_timesheet', 'export_own_timesheet', 'delete_own_timesheet'];
            $timesheetOthers = ['view_other_timesheet', 'start_other_timesheet', 'stop_other_timesheet', 'create_other_timesheet', 'edit_other_timesheet',  'export_other_timesheet', 'delete_other_timesheet'];
            $profile = ['view_own_profile', 'edit_own_profile', 'password_own_profile', 'preferences_own_profile', 'api-token_own_profile'];
            $profileOther = ['view_other_profile', 'edit_other_profile', 'password_other_profile', 'roles_other_profile', 'preferences_other_profile', 'api-token_other_profile'];
            $user = ['view_user', 'create_user', 'delete_user'];
            $rate = ['view_rate_own_timesheet', 'edit_rate_own_timesheet'];
            $rateOther = ['view_rate_other_timesheet', 'edit_rate_other_timesheet'];
            $teams = ['view_team', 'create_team', 'edit_team', 'delete_team'];

            $roleUser = ['view_team_member', 'edit_team_activity', 'edit_team_project', 'edit_team_customer'];
            $roleTeamlead = ['view_team_member', 'view_rate_own_timesheet', 'view_rate_other_timesheet', 'hourly-rate_own_profile'];
            $roleAdmin = ['view_team_member', 'hourly-rate_own_profile', 'edit_exported_timesheet'];
            $roleSuperAdmin = ['view_team_member', 'hourly-rate_own_profile', 'hourly-rate_other_profile', 'roles_own_profile', 'system_information', 'system_configuration', 'plugins', 'edit_exported_timesheet'];

            $permissions = [
                'ROLE_USER' => array_merge($timesheet, $profile, $roleUser),
                'ROLE_TEAMLEAD' => array_merge($invoice, $timesheet, $timesheetOthers, $profile, $roleTeamlead, $activitiesTeam, $projectsTeam, $customersTeam),
                'ROLE_ADMIN' => array_merge($activities, $projects, $customers, $invoice, $invoiceTemplate, $timesheet, $timesheetOthers, $profile, $rate, $rateOther, $roleAdmin, $teams),
                'ROLE_SUPER_ADMIN' => array_merge($activities, $projects, $customers, $invoice, $invoiceTemplate, $timesheet, $timesheetOthers, $profile, $profileOther, $user, $rate, $rateOther, $roleSuperAdmin, $teams),
            ];
        }

        $repository = $this->getMockBuilder(RolePermissionRepository::class)->onlyMethods(['getAllAsArray'])->disableOriginalConstructor()->getMock();
        $repository->method('getAllAsArray')->willReturn([]);

        $names = [];
        $perms = [];
        foreach ($permissions as $role => $permissionNames) {
            $perms[$role] = [];
            foreach ($permissionNames as $name) {
                $perms[$role][$name] = true;
                $names[$name] = true;
            }
        }
        /** @var RolePermissionRepository $repository */
        $service = new PermissionService($repository, new ArrayAdapter());

        return new RolePermissionManager($service, $perms, $names);
    }
}
