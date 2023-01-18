<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\User as UserEntity;
use App\Tests\Security\TestUserEntity;
use App\User\UserService;
use App\Validator\Constraints\User;
use App\Validator\Constraints\UserValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\User
 * @covers \App\Validator\Constraints\UserValidator
 * @extends ConstraintValidatorTestCase<UserValidator>
 */
class UserValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): UserValidator
    {
        $userService = $this->createMock(UserService::class);

        return new UserValidator($userService);
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank()); // @phpstan-ignore-line
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new User(['message' => 'myMessage'])); // @phpstan-ignore-line

        $this->assertNoViolation();
    }

    public function testNonUserIsValid()
    {
        $this->validator->validate(new TestUserEntity(), new User(['message' => 'myMessage'])); // @phpstan-ignore-line

        $this->assertNoViolation();
    }

    public function testEmptyUserIsValid()
    {
        $user = new UserEntity();
        $user->setUserIdentifier('foo');
        $user->setEmail('test');
        $this->validator->validate($user, new User(['message' => 'myMessage']));

        $this->assertNoViolation();
    }

    public function testUserIsValidWithEmptyRepository()
    {
        $user = new UserEntity();
        $user->setUserIdentifier('foo');
        $user->setEmail('foo@example.com');

        $this->validator->validate($user, new User(['message' => 'myMessage']));

        $this->assertNoViolation();
    }

    public function testUserIsInvalidWithRepository()
    {
        $existing = $this->createMock(UserEntity::class);
        $existing->expects($this->exactly(4))->method('getId')->willReturn(123);

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->exactly(2))->method('findUserByEmail')->willReturn($existing);
        $userService->expects($this->exactly(2))->method('findUserByName')->willReturn($existing);

        $this->validator = new UserValidator($userService);
        $this->validator->initialize($this->context);

        $user = new UserEntity();
        $user->setUserIdentifier('foo');
        $user->setEmail('foo@example.com');

        $this->validator->validate($user, new User());

        $this
            ->buildViolation('The email is already used.')
            ->atPath('property.path.email')
            ->setCode(User::USER_EXISTING_EMAIL)
            ->buildNextViolation('An equal username is already used.')
            ->atPath('property.path.email')
            ->setCode(User::USER_EXISTING_EMAIL_AS_NAME)
            ->buildNextViolation('An equal email is already used.')
            ->atPath('property.path.username')
            ->setCode(User::USER_EXISTING_NAME_AS_EMAIL)
            ->buildNextViolation('The username is already used.')
            ->atPath('property.path.username')
            ->setCode(User::USER_EXISTING_NAME)
            ->assertRaised();
    }
}
