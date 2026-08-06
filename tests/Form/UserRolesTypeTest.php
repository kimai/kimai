<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\Extension\UserExtension;
use App\Form\Type\UserRoleType;
use App\Form\UserRolesType;
use App\Repository\RoleRepository;
use App\Security\RoleService;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(UserRolesType::class)]
class UserRolesTypeTest extends TypeTestCase
{
    /**
     * The user performing the edit; resolved through the (mocked) Security into the "user" option,
     * exactly like in production, so both the nested choice filter and the merge logic see it.
     */
    private ?User $actingUser = null;

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

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturnCallback(fn () => $this->actingUser);
        $userExtension = new UserExtension($security);

        return [
            new PreloadedExtension(
                [new UserRoleType($roleService)],
                [FormType::class => [$userExtension]],
            ),
        ];
    }

    private function createUserWithRoles(string ...$roles): User
    {
        $user = new User();
        $user->setRoles($roles);

        return $user;
    }

    /**
     * @return string[]
     */
    private function submitRoles(User $target, array $submittedRoles): array
    {
        $form = $this->factory->create(UserRolesType::class, $target, ['csrf_protection' => false]);
        $form->submit(['roles' => $submittedRoles]);

        self::assertTrue($form->isSynchronized());

        return $target->getRoles();
    }

    public function testHigherRoleIsPreservedWhenActorCannotAssignIt(): void
    {
        $this->actingUser = $this->createUserWithRoles(User::ROLE_ADMIN);
        $target = $this->createUserWithRoles(User::ROLE_SUPER_ADMIN);

        // the admin only submits a role they are allowed to assign
        $roles = $this->submitRoles($target, [User::ROLE_TEAMLEAD]);

        // the assignable change is applied, but the non-assignable super admin role survives
        self::assertContains(User::ROLE_TEAMLEAD, $roles);
        self::assertContains(User::ROLE_SUPER_ADMIN, $roles);
    }

    public function testMultipleHigherRolesArePreserved(): void
    {
        $this->actingUser = $this->createUserWithRoles(User::ROLE_TEAMLEAD);
        $target = $this->createUserWithRoles(User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN);

        // a teamlead may neither assign admin nor super admin, so both must be kept
        $roles = $this->submitRoles($target, [User::ROLE_TEAMLEAD]);

        self::assertContains(User::ROLE_TEAMLEAD, $roles);
        self::assertContains(User::ROLE_ADMIN, $roles);
        self::assertContains(User::ROLE_SUPER_ADMIN, $roles);
    }

    public function testAssignableRoleCanStillBeRemoved(): void
    {
        $this->actingUser = $this->createUserWithRoles(User::ROLE_ADMIN);
        $target = $this->createUserWithRoles(User::ROLE_TEAMLEAD);

        // the teamlead role is assignable by the admin, so not submitting it removes it
        $roles = $this->submitRoles($target, [User::ROLE_ADMIN]);

        self::assertContains(User::ROLE_ADMIN, $roles);
        self::assertNotContains(User::ROLE_TEAMLEAD, $roles);
    }

    public function testActorCanRemoveARoleTheyAreAllowedToAssign(): void
    {
        // a super admin may manage the super admin role, so it is not preserved automatically
        $this->actingUser = $this->createUserWithRoles(User::ROLE_SUPER_ADMIN);
        $target = $this->createUserWithRoles(User::ROLE_SUPER_ADMIN);

        $roles = $this->submitRoles($target, [User::ROLE_TEAMLEAD]);

        self::assertContains(User::ROLE_TEAMLEAD, $roles);
        self::assertNotContains(User::ROLE_SUPER_ADMIN, $roles);
    }

    public function testWithoutActingUserNothingIsPreserved(): void
    {
        // e.g. CLI / anonymous context: no filtering happens, so a plain submit replaces the roles
        $this->actingUser = null;
        $target = $this->createUserWithRoles(User::ROLE_SUPER_ADMIN);

        $roles = $this->submitRoles($target, [User::ROLE_TEAMLEAD]);

        self::assertContains(User::ROLE_TEAMLEAD, $roles);
        self::assertNotContains(User::ROLE_SUPER_ADMIN, $roles);
    }
}
