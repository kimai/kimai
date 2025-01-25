<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\User;
use App\Tests\Mocks\Security\RoleServiceFactory;
use App\Validator\Constraints\Role;
use App\Validator\Constraints\RoleValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\Role
 * @covers \App\Validator\Constraints\RoleValidator
 * @extends ConstraintValidatorTestCase<RoleValidator>
 */
class RoleValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): RoleValidator
    {
        $factory = new RoleServiceFactory($this);
        $roleService = $factory->create();

        return new RoleValidator($roleService);
    }

    public static function getValidRoles()
    {
        return [
            [User::ROLE_USER],
            [User::ROLE_TEAMLEAD],
            [User::ROLE_ADMIN],
            [User::ROLE_SUPER_ADMIN],
        ];
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    /**
     * @dataProvider getValidRoles
     */
    public function testConstraintWithValidRole(string $role): void
    {
        $constraint = new Role();
        $this->validator->validate($role, $constraint);
        $this->assertNoViolation();
    }

    public function testNullIsInvalid(): void
    {
        $this->validator->validate(null, new Role(['message' => 'myMessage']));

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', 'null')
            ->setCode(Role::ROLE_ERROR)
            ->assertRaised();
    }

    public static function getInvalidRoles()
    {
        return [
            ['foo'],
            [0],
            ['role_user'],
            ['ROLE-CUSTOMER'],
            ['anonymous'],
            [''],
        ];
    }

    /**
     * @dataProvider getInvalidRoles
     */
    public function testValidationError(mixed $role): void
    {
        $constraint = new Role([
            'message' => 'myMessage',
        ]);

        $this->validator->validate($role, $constraint);

        $expectedFormat = \is_string($role) ? '"' . $role . '"' : (string) $role;

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', $expectedFormat)
            ->setCode(Role::ROLE_ERROR)
            ->assertRaised();
    }
}
