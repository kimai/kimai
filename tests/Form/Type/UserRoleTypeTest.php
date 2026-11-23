<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Entity\User;
use App\Form\Extension\UserExtension;
use App\Form\Type\UserRoleType;
use App\Repository\RoleRepository;
use App\Security\RoleService;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(UserRoleType::class)]
class UserRoleTypeTest extends TypeTestCase
{
    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        $repository = $this->createMock(RoleRepository::class);
        $repository->method('findAll')->willReturn([]);

        $roleService = new RoleService($repository, [
            User::ROLE_USER,
            User::ROLE_TEAMLEAD,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN,
        ]);

        $type = new UserRoleType($roleService);

        // the "user" option (the acting user) is provided globally by the UserExtension on FormType;
        // getUser() returns null here as every test passes the acting user explicitly via the field options
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);
        $userExtension = new UserExtension($security);

        return [
            new PreloadedExtension([$type], [FormType::class => [$userExtension]]),
        ];
    }

    /**
     * @return string[] the role names offered as assignable choices
     */
    private function getAssignableRoles(?User $actingUser, bool $includeDefault = false, bool $restrictToAssignable = true): array
    {
        $builder = $this->factory->createBuilder(FormType::class);
        $builder->add('roles', UserRoleType::class, [
            'user' => $actingUser,
            'include_default' => $includeDefault,
            'restrict_to_assignable' => $restrictToAssignable,
        ]);
        $form = $builder->getForm();

        $choices = $form->get('roles')->getConfig()->getOption('choices');
        \assert(\is_array($choices));

        return array_map(strval(...), array_keys($choices));
    }

    private function createUserWithRole(?string $role): User
    {
        $user = new User();
        if ($role !== null) {
            $user->addRole($role);
        }

        return $user;
    }

    public function testSuperAdminCanAssignAllRoles(): void
    {
        $roles = $this->getAssignableRoles($this->createUserWithRole(User::ROLE_SUPER_ADMIN));

        self::assertContains(User::ROLE_SUPER_ADMIN, $roles);
        self::assertContains(User::ROLE_ADMIN, $roles);
        self::assertContains(User::ROLE_TEAMLEAD, $roles);
    }

    public function testAdminCannotAssignSuperAdmin(): void
    {
        $roles = $this->getAssignableRoles($this->createUserWithRole(User::ROLE_ADMIN));

        self::assertNotContains(User::ROLE_SUPER_ADMIN, $roles);
        self::assertContains(User::ROLE_ADMIN, $roles);
        self::assertContains(User::ROLE_TEAMLEAD, $roles);
    }

    public function testTeamleadCanOnlyAssignTeamlead(): void
    {
        $roles = $this->getAssignableRoles($this->createUserWithRole(User::ROLE_TEAMLEAD));

        self::assertNotContains(User::ROLE_SUPER_ADMIN, $roles);
        self::assertNotContains(User::ROLE_ADMIN, $roles);
        self::assertContains(User::ROLE_TEAMLEAD, $roles);
    }

    public function testRegularUserCannotAssignPrivilegedRoles(): void
    {
        $roles = $this->getAssignableRoles($this->createUserWithRole(null));

        self::assertNotContains(User::ROLE_SUPER_ADMIN, $roles);
        self::assertNotContains(User::ROLE_ADMIN, $roles);
        self::assertNotContains(User::ROLE_TEAMLEAD, $roles);
    }

    public function testWithoutActingUserAllRolesRemain(): void
    {
        // no acting user (e.g. CLI or anonymous forms) => no privilege based filtering
        $roles = $this->getAssignableRoles(null);

        self::assertContains(User::ROLE_SUPER_ADMIN, $roles);
        self::assertContains(User::ROLE_ADMIN, $roles);
        self::assertContains(User::ROLE_TEAMLEAD, $roles);
    }

    public function testAssignabilityRestrictionIsOptInAndOffByDefault(): void
    {
        // by default (e.g. the toolbar filter field) no privilege based filtering is applied,
        // so even a regular user sees every role to filter/export by
        $roles = $this->getAssignableRoles($this->createUserWithRole(User::ROLE_USER), false, false);

        self::assertContains(User::ROLE_SUPER_ADMIN, $roles);
        self::assertContains(User::ROLE_ADMIN, $roles);
        self::assertContains(User::ROLE_TEAMLEAD, $roles);
    }

    public function testDefaultRoleIsExcludedByDefaultButCanBeIncluded(): void
    {
        $actingUser = $this->createUserWithRole(User::ROLE_SUPER_ADMIN);

        self::assertNotContains(User::DEFAULT_ROLE, $this->getAssignableRoles($actingUser));
        self::assertContains(User::DEFAULT_ROLE, $this->getAssignableRoles($actingUser, true));
    }

    public function testSubmittingANonAssignableRoleIsNotApplied(): void
    {
        // an admin must not be able to escalate a user to super admin by crafting the request:
        // the role is not among the offered choices and is therefore dropped instead of applied
        $form = $this->createRolesForm(User::ROLE_ADMIN);
        $form->submit([User::ROLE_SUPER_ADMIN]);

        self::assertNotContains(User::ROLE_SUPER_ADMIN, (array) $form->getData());
    }

    public function testSubmittingAnAssignableRoleIsApplied(): void
    {
        // a role the acting admin is allowed to assign passes through the form
        $form = $this->createRolesForm(User::ROLE_ADMIN);
        $form->submit([User::ROLE_ADMIN]);

        self::assertContains(User::ROLE_ADMIN, (array) $form->getData());
    }

    /**
     * Builds a standalone roles field configured like the production role edit form (multiple + expanded).
     *
     * @return \Symfony\Component\Form\FormInterface<mixed>
     */
    private function createRolesForm(?string $actingUserRole): \Symfony\Component\Form\FormInterface
    {
        $builder = $this->factory->createBuilder(FormType::class);
        $builder->add('roles', UserRoleType::class, [
            'user' => $this->createUserWithRole($actingUserRole),
            'multiple' => true,
            'expanded' => true,
            'restrict_to_assignable' => true,
        ]);

        return $builder->getForm()->get('roles');
    }
}
