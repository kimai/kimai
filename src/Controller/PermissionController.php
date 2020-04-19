<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Role;
use App\Entity\RolePermission;
use App\Event\PermissionSectionsEvent;
use App\Event\PermissionsEvent;
use App\Form\RoleType;
use App\Model\PermissionSection;
use App\Repository\RolePermissionRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Security\RolePermissionManager;
use App\Security\RoleService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage user roles and role permissions.
 *
 * @Route(path="/admin/permissions")
 * @Security("is_granted('role_permissions')")
 */
final class PermissionController extends AbstractController
{
    /**
     * @var RoleService
     */
    private $roleService;
    /**
     * @var RolePermissionManager
     */
    private $manager;
    /**
     * @var RoleRepository
     */
    private $roleRepository;

    public function __construct(RoleService $roleService, RolePermissionManager $manager, RoleRepository $roleRepository)
    {
        $this->roleService = $roleService;
        $this->manager = $manager;
        $this->roleRepository = $roleRepository;
    }

    /**
     * @Route(path="", name="admin_user_permissions", methods={"GET", "POST"})
     * @Security("is_granted('role_permissions')")
     */
    public function permissions(EventDispatcherInterface $dispatcher)
    {
        $all = $this->roleRepository->findAll();
        $existing = [];

        foreach ($all as $role) {
            $existing[] = $role->getName();
        }

        $existing = array_map('strtoupper', $existing);

        // automatically import all hard coded (default) roles into the database table
        foreach ($this->roleService->getAvailableNames() as $roleName) {
            $roleName = strtoupper($roleName);
            if (!\in_array($roleName, $existing)) {
                $role = new Role();
                $role->setName($roleName);
                $this->roleRepository->saveRole($role);
                $existing[] = $roleName;
            }
        }

        // be careful, the order of the search keys is important!
        $permissionOrder = [
            new PermissionSection('Export', '_export'),
            new PermissionSection('Invoice', '_invoice'),
            new PermissionSection('Teams', '_team'),
            new PermissionSection('Tags', '_tag'),
            new PermissionSection('User profile (other)', '_other_profile'),
            new PermissionSection('User profile (own)', '_own_profile'),
            new PermissionSection('User', '_user'),
            new PermissionSection('Customer (Admin)', '_customer'),
            new PermissionSection('Customer (Team member)', '_team_customer'),
            new PermissionSection('Customer (Teamlead)', '_teamlead_customer'),
            new PermissionSection('Project (Admin)', '_project'),
            new PermissionSection('Project (Team member)', '_team_project'),
            new PermissionSection('Project (Teamlead)', '_teamlead_project'),
            new PermissionSection('Activity', '_activity'),
            new PermissionSection('Timesheet', '_timesheet'),
            new PermissionSection('Timesheet (other)', '_other_timesheet'),
            new PermissionSection('Timesheet (own)', '_own_timesheet'),
        ];

        $event = new PermissionSectionsEvent();
        foreach ($permissionOrder as $section) {
            $event->addSection($section);
        }
        $dispatcher->dispatch($event);

        $permissionSorted = [];
        $other = [];

        foreach ($event->getSections() as $section) {
            $permissionSorted[$section->getTitle()] = [];
        }

        foreach ($this->manager->getPermissions() as $permission) {
            $found = false;

            foreach (array_reverse($event->getSections()) as $section) {
                if ($section->filter($permission)) {
                    $permissionSorted[$section->getTitle()][] = $permission;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $other[] = $permission;
            }
        }

        ksort($permissionSorted);

        $permissionSorted['Other'] = $other;

        // order the roles from most powerful to least powerful, custom roles at the end
        $roles = [
            'ROLE_SUPER_ADMIN' => null,
            'ROLE_ADMIN' => null,
            'ROLE_TEAMLEAD' => null,
            'ROLE_USER' => null,
        ];
        foreach ($this->roleRepository->findAll() as $role) {
            $roles[$role->getName()] = $role;
        }

        $event = new PermissionsEvent();
        foreach ($permissionSorted as $title => $permissions) {
            $event->addPermissions($title, $permissions);
        }

        $dispatcher->dispatch($event);

        return $this->render('user/permissions.html.twig', [
            'roles' => array_values($roles),
            'sorted' => $event->getPermissions(),
            'manager' => $this->manager,
            'system_roles' => $this->roleService->getSystemRoles(),
        ]);
    }

    /**
     * @Route(path="/roles/create", name="admin_user_roles", methods={"GET", "POST"})
     * @Security("is_granted('role_permissions')")
     */
    public function createRole(Request $request): Response
    {
        $role = new Role();

        $form = $this->createForm(RoleType::class, $role, [
            'action' => $this->generateUrl('admin_user_roles', []),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->roleRepository->saveRole($role);
                $this->flashSuccess('action.update.success');
            } catch (\Exception $ex) {
                $this->flashSuccess('action.update.error');
            }

            return $this->redirectToRoute('admin_user_permissions');
        }

        return $this->render('user/edit_role.html.twig', [
            'form' => $form->createView(),
            'role' => $role,
        ]);
    }

    /**
     * @Route(path="/roles/{id}/delete", name="admin_user_role_delete", methods={"GET", "POST"})
     * @Security("is_granted('role_permissions')")
     */
    public function deleteRole(Role $role, UserRepository $userRepository): Response
    {
        try {
            // workaround, as roles is still a string array on users table
            // until this is fixed, the users must be manually updated
            $users = $userRepository->findUsersWithRole($role->getName());
            foreach ($users as $user) {
                $user->removeRole($role->getName());
                $userRepository->saveUser($user);
            }
            $this->roleRepository->deleteRole($role);
            $this->flashSuccess('action.delete.success');
        } catch (\Exception $ex) {
            $this->flashError('action.delete.error');
        }

        return $this->redirectToRoute('admin_user_permissions');
    }

    /**
     * @Route(path="/roles/{id}/{name}/{value}", name="admin_user_permission_save", methods={"GET"})
     * @Security("is_granted('role_permissions')")
     */
    public function savePermission(Role $role, string $name, string $value, RolePermissionRepository $rolePermissionRepository): Response
    {
        if (!$this->manager->isRegisteredPermission($name)) {
            throw $this->createNotFoundException('Unknown permission: ' . $name);
        }

        try {
            $permission = $rolePermissionRepository->findRolePermission($role, $name);
            if (null === $permission) {
                $permission = new RolePermission();
                $permission->setRole($role);
                $permission->setPermission($name);
            }
            $permission->setAllowed((bool) $value);

            $rolePermissionRepository->saveRolePermission($permission);
            $this->flashSuccess('action.update.success');
        } catch (\Exception $ex) {
            $this->flashError('action.update.error');
        }

        return $this->redirectToRoute('admin_user_permissions');
    }
}
