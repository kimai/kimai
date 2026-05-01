<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\User\PermissionService;
use Doctrine\Common\Collections\Collection;

final class RolePermissionManager
{
    /**
     * Permissions that are always true for ROLE_SUPER_ADMIN, no matter what is inside the database.
     *
     * @var array<string, bool>
     * @internal
     */
    public const SUPER_ADMIN_PERMISSIONS = [
        'view_all_data' => true,
        'role_permissions' => true,
        'view_user' => true,
    ];

    private bool $isInitialized = false;

    /**
     * @param array<string, array<string, bool>> $permissions as defined in kimai.yaml
     * @param array<string, bool> $permissionNames as defined in kimai.yaml
     */
    public function __construct(
        private readonly PermissionService $service,
        private array $permissions,
        private readonly array $permissionNames
    )
    {
    }

    private function init(): void
    {
        if ($this->isInitialized) {
            return;
        }

        foreach ($this->service->getPermissions() as $item) {
            $perm = (string) $item['permission'];
            $role = (string) $item['role'];

            if (!\array_key_exists($role, $this->permissions)) {
                $this->permissions[$role] = [];
            }

            $this->permissions[$role][$perm] = (bool) $item['allowed'];
        }

        // these permissions may not be revoked at any time, because super admin would lose the ability to reactivate any permission
        foreach (self::SUPER_ADMIN_PERMISSIONS as $perm => $value) {
            $this->permissions[User::ROLE_SUPER_ADMIN][$perm] = $value;
        }

        $this->isInitialized = true;
    }

    /**
     * Only permissions which were registered through the Symfony configuration stack will be acknowledged here.
     */
    public function isRegisteredPermission(string $permission): bool
    {
        return \array_key_exists($permission, $this->permissionNames);
    }

    public function hasPermission(string $role, string $permission): bool
    {
        $this->init();

        $role = strtoupper($role);

        if (!\array_key_exists($role, $this->permissions)) {
            return false;
        }

        return \array_key_exists($permission, $this->permissions[$role]) && $this->permissions[$role][$permission];
    }

    public function hasRolePermission(User $user, string $permission): bool
    {
        $this->init();

        foreach ($user->getRoles() as $role) {
            if ($this->hasPermission($role, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Only permissions which were registered through the Symfony configuration stack will be returned here.
     *
     * @return array<string>
     */
    public function getPermissions(): array
    {
        return array_keys($this->permissionNames);
    }

    /**
     * @param array<int, Team>|Collection<int, Team> $teams
     */
    private function checkTeamAccess(Collection|array $teams, User $user): bool
    {
        if ($user->canSeeAllData()) {
            return true;
        }

        if (\count($teams) === 0) {
            return true;
        }

        foreach ($teams as $team) {
            if ($user->isInTeam($team)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, Team>|Collection<int, Team> $teams
     */
    private function checkTeamLeadAccess(Collection|array $teams, User $user): bool
    {
        if ($user->canSeeAllData()) {
            return true;
        }

        if (\count($teams) === 0) {
            return true;
        }

        foreach ($teams as $team) {
            if ($user->isTeamleadOf($team)) {
                return true;
            }
        }

        return false;
    }

    public function checkTeamAccessCustomer(Customer $customer, User $user): bool
    {
        return $this->checkTeamAccess($customer->getTeams(), $user);
    }

    public function checkTeamAccessProject(Project $project, User $user): bool
    {
        if ($project->getCustomer() !== null && !$this->checkTeamAccessCustomer($project->getCustomer(), $user)) {
            return false;
        }

        return $this->checkTeamAccess($project->getTeams(), $user);
    }

    public function checkTeamAccessActivity(Activity $activity, User $user): bool
    {
        if ($activity->getProject() !== null && !$this->checkTeamAccessProject($activity->getProject(), $user)) {
            return false;
        }

        return $this->checkTeamAccess($activity->getTeams(), $user);
    }

    public function checkTeamAccessTimesheet(Timesheet $timesheet, User $user): bool
    {
        if ($user->getId() !== null && $user->getId() === $timesheet->getUser()?->getId()) {
            return true;
        }

        if ($timesheet->getProject() !== null && !$this->checkTeamAccessProject($timesheet->getProject(), $user)) {
            return false;
        }

        if ($timesheet->getActivity() !== null && !$this->checkTeamAccessActivity($timesheet->getActivity(), $user)) {
            return false;
        }

        return $this->checkTeamLeadAccess($timesheet->getUser()?->getTeams() ?? [], $user);
    }
}
