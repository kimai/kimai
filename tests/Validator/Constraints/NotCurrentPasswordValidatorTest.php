<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\User;
use App\Validator\Constraints\NotCurrentPassword;
use App\Validator\Constraints\NotCurrentPasswordValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<NotCurrentPasswordValidator>
 */
#[CoversClass(NotCurrentPassword::class)]
#[CoversClass(NotCurrentPasswordValidator::class)]
class NotCurrentPasswordValidatorTest extends ConstraintValidatorTestCase
{
    private bool $isPasswordValid = false;

    protected function createValidator(): NotCurrentPasswordValidator
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturnCallback(fn () => $this->isPasswordValid);

        return new NotCurrentPasswordValidator($hasher);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new User(), new NotBlank());
    }

    public function testGetTargets(): void
    {
        $constraint = new NotCurrentPassword();
        self::assertEquals('class', $constraint->getTargets());
    }

    public function testNonUserIsValid(): void
    {
        $this->isPasswordValid = true;
        $this->validator->validate('foo', new NotCurrentPassword());

        $this->assertNoViolation();
    }

    public function testEmptyPlainPasswordIsValid(): void
    {
        $this->isPasswordValid = true;
        $user = new User();
        $user->setPassword('hashed-password');

        $this->validator->validate($user, new NotCurrentPassword());

        $this->assertNoViolation();
    }

    public function testEmptyCurrentPasswordIsValid(): void
    {
        $this->isPasswordValid = true;
        $user = new User();
        $user->setPlainPassword('new-password');

        $this->validator->validate($user, new NotCurrentPassword());

        $this->assertNoViolation();
    }

    public function testDifferentPasswordIsValid(): void
    {
        $this->isPasswordValid = false;
        $user = new User();
        $user->setPassword('hashed-password');
        $user->setPlainPassword('new-password');

        $this->validator->validate($user, new NotCurrentPassword());

        $this->assertNoViolation();
    }

    public function testCurrentPasswordIsInvalid(): void
    {
        $this->isPasswordValid = true;
        $user = new User();
        $user->setPassword('hashed-password');
        $user->setPlainPassword('current-password');

        $this->validator->validate($user, new NotCurrentPassword());

        $this->buildViolation('The new password must be different from the current password.')
            ->atPath('property.path.plainPassword')
            ->setCode(NotCurrentPassword::SAME_AS_CURRENT_PASSWORD)
            ->assertRaised();
    }
}
