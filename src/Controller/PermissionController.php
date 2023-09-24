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
use App\Entity\User;
use App\Event\PermissionSectionsEvent;
use App\Event\PermissionsEvent;
use App\Form\RoleType;
use App\Model\PermissionSection;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Security\RolePermissionManager;
use App\Security\RoleService;
use App\User\PermissionService;
use App\Utils\PageSetup;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage user roles and role permissions.
 */
#[Route(path: '/admin/permissions')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[IsGranted('role_permissions')]
final class PermissionController extends AbstractController
{
    public const TOKEN_NAME = 'user_role_permissions';

    public function __construct(private RolePermissionManager $manager, private RoleRepository $roleRepository)
    {
    }

    #[Route(path: '', name: 'admin_user_permissions', methods: ['GET', 'POST'])]
    #[IsGranted('role_permissions')]
    public function permissions(EventDispatcherInterface $dispatcher, CsrfTokenManagerInterface $csrfTokenManager, RoleService $roleService): Response
    {
        $all = $this->roleRepository->findAll();
        $existing = [];

        foreach ($all as $role) {
            $existing[] = $role->getName();
        }

        $existing = array_map('strtoupper', $existing);

        // automatically import all hard coded (default) roles into the database table
        foreach ($roleService->getAvailableNames() as $roleName) {
            if (!\in_array($roleName, $existing)) {
                $role = new Role();
                $role->setName($roleName);
                $this->roleRepository->saveRole($role);
                $existing[] = $roleName;
                $all[] = $role;
            }
        }

        // be careful, the order of the search keys is important!
        // @CloudRequired (names should not change)
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
            new PermissionSection('Activity (Admin)', '_activity'),
            new PermissionSection('Activity (Team member)', '_team_activity'),
            new PermissionSection('Activity (Teamlead)', '_teamlead_activity'),
            new PermissionSection('Timesheet', '_timesheet'),
            new PermissionSection('Timesheet (other)', '_other_timesheet'),
            new PermissionSection('Timesheet (own)', '_own_timesheet'),
            new PermissionSection('Reporting', '_reporting'),
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
        foreach ($all as $role) {
            $roles[$role->getName()] = $role;
        }
        $default = $roles['ROLE_USER'];
        unset($roles['ROLE_USER']);
        $roles['ROLE_USER'] = $default;

        $event = new PermissionsEvent();
        foreach ($permissionSorted as $title => $permissions) {
            $event->addPermissions($title, $permissions);
        }

        $dispatcher->dispatch($event);

        $page = new PageSetup('profile.roles');
        $page->setHelp('permissions.html');
        $page->setActionName('user_permissions');

        return $this->render('permission/permissions.html.twig', [
            'page_setup' => $page,
            'token' => $csrfTokenManager->refreshToken(self::TOKEN_NAME)->getValue(),
            'roles' => array_values($roles),
            'sorted' => $event->getPermissions(),
            'manager' => $this->manager,
            'system_roles' => $roleService->getSystemRoles(),
            'always_apply_superadmin' => array_keys(RolePermissionManager::SUPER_ADMIN_PERMISSIONS),
        ]);
    }

    #[Route(path: '/roles/create', name: 'admin_user_roles', methods: ['GET', 'POST'])]
    #[IsGranted('role_permissions')]
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
                $this->flashUpdateException($ex);
            }

            return $this->redirectToRoute('admin_user_permissions');
        }

        $page = new PageSetup('profile.roles');
        $page->setHelp('permissions.html');

        return $this->render('permission/edit_role.html.twig', [
            'page_setup' => $page,
            'form' => $form->createView(),
            'role' => $role,
        ]);
    }

    #[Route(path: '/roles/{id}/delete/{csrfToken}', name: 'admin_user_role_delete', methods: ['GET', 'POST'])]
    #[IsGranted('role_permissions')]
    public function deleteRole(Role $role, string $csrfToken, UserRepository $userRepository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$this->isCsrfTokenValid(self::TOKEN_NAME, $csrfToken)) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('admin_user_permissions');
        }

        // make sure that the token can only be used once, so refresh it after successful submission
        $csrfTokenManager->refreshToken(self::TOKEN_NAME)->getValue();

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
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('admin_user_permissions');
    }

    #[Route(path: '/roles/{id}/{name}/{value}/{csrfToken}', name: 'admin_user_permission_save', methods: ['POST'])]
    #[IsGranted('role_permissions')]
    public function savePermission(Role $role, string $name, bool $value, string $csrfToken, PermissionService $permissionService, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$this->isCsrfTokenValid(self::TOKEN_NAME, $csrfToken)) {
            throw new BadRequestHttpException('Invalid CSRF token');
        }

        if (!$this->manager->isRegisteredPermission($name)) {
            throw $this->createNotFoundException('Unknown permission: ' . $name);
        }

        if (false === $value && $role->getName() === User::ROLE_SUPER_ADMIN && \array_key_exists($name, RolePermissionManager::SUPER_ADMIN_PERMISSIONS)) {
            throw new BadRequestHttpException(sprintf('Permission "%s" cannot be deactivated for role "%s"', $name, $role->getName()));
        }

        try {
            $permission = $permissionService->findRolePermission($role, $name);
            if (null === $permission) {
                $permission = new RolePermission();
                $permission->setRole($role);
                $permission->setPermission($name);
            }
            $permission->setAllowed($value);

            $permissionService->saveRolePermission($permission);

            // refreshToken instead of getToken for more security but worse UX
            // fast clicking with slow response times would fail, as the token cannot be replaced fast enough
            $newToken = $csrfTokenManager->getToken(self::TOKEN_NAME)->getValue();

            return $this->json(['token' => $newToken]);
        } catch (\Exception $ex) {
            $this->flashUpdateException($ex);
        }

        throw new BadRequestHttpException();
    }
}
