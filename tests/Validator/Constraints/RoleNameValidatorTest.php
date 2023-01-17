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
use App\Validator\Constraints\RoleName;
use App\Validator\Constraints\RoleNameValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\RoleName
 * @covers \App\Validator\Constraints\RoleNameValidator
 * @extends ConstraintValidatorTestCase<RoleNameValidator>
 */
class RoleNameValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): RoleNameValidator
    {
        $factory = new RoleServiceFactory($this);

        return new RoleNameValidator($factory->create());
    }

    /**
     * @return array<array<int, string>>
     */
    public function getValidRoleNames(): array
    {
        return [
            ['FOOBAR'],
            ['ROLE_CUSTOMER'],
            ['ANONYMOUS'],
            ['TESTA'],
            ['TE_ST'],
        ];
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    /**
     * @dataProvider getValidRoleNames
     * @param string $role
     */
    public function testConstraintWithValidRole(string $role): void
    {
        $constraint = new RoleName();
        $this->validator->validate($role, $constraint);
        $this->assertNoViolation();
    }

    public function testNullIsInvalid(): void
    {
        $this->validator->validate(null, new RoleName(['message' => 'myMessage']));

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', 'null')
            ->setCode(RoleName::ROLE_NAME_ERROR)
            ->assertRaised();
    }

    /**
     * @return array<array<string|int>>
     */
    public function getInvalidRoleNames(): array
    {
        return [
            ['foo'],
            ['foobar'],
            [0],
            ['role_user'],
            ['ROLE-CUSTOMER'],
            ['anonymous'],
            [''],
            ['_TESTA'],
            ['TESTA_'],
            ['TE__ST'],
            ['_______'],
            [User::ROLE_USER],
            [User::ROLE_TEAMLEAD],
            [User::ROLE_ADMIN],
            [User::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @dataProvider getInvalidRoleNames
     */
    public function testValidationError(string|int $role): void
    {
        $constraint = new RoleName([
            'message' => 'myMessage',
        ]);

        $this->validator->validate($role, $constraint);

        $expectedFormat = \is_string($role) ? '"' . $role . '"' : (string) $role;

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', $expectedFormat)
            ->setCode(RoleName::ROLE_NAME_ERROR)
            ->assertRaised();
    }
}
