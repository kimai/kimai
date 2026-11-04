<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Tests\Mocks\SystemConfigurationFactory;
use App\Validator\Constraints\PasswordPolicy;
use App\Validator\Constraints\PasswordPolicyValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PasswordPolicyValidator>
 */
#[CoversClass(PasswordPolicy::class)]
#[CoversClass(PasswordPolicyValidator::class)]
class PasswordPolicyValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(int $strength = PasswordStrength::STRENGTH_MEDIUM): PasswordPolicyValidator
    {
        $configuration = SystemConfigurationFactory::createStub(['user' => ['password_strength' => $strength]]);

        return new PasswordPolicyValidator($configuration);
    }

    private function setStrength(int $strength): void
    {
        $this->validator = $this->createValidator($strength);
        $this->validator->initialize($this->context);
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new PasswordPolicy());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new PasswordPolicy());

        $this->assertNoViolation();
    }

    public function testDisabledPolicyAllowsWeakPassword(): void
    {
        $this->setStrength(0);
        $this->validator->validate('test1234', new PasswordPolicy());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<array{0: int, 1: string}>
     */
    public static function getValidPasswords(): iterable
    {
        yield [PasswordStrength::STRENGTH_WEAK, 'kimai-timetrack'];
        yield [PasswordStrength::STRENGTH_MEDIUM, 'kimai-time-tracking'];
        yield [PasswordStrength::STRENGTH_STRONG, 'Kimai-Time-Tracking-2'];
        yield [PasswordStrength::STRENGTH_VERY_STRONG, 'Kx9#mP2$vL7!qR4@wZ5%tB8&'];
    }

    #[DataProvider('getValidPasswords')]
    public function testStrongPasswordIsValid(int $strength, string $password): void
    {
        $this->setStrength($strength);
        $this->validator->validate($password, new PasswordPolicy());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<array{0: int, 1: string}>
     */
    public static function getInvalidPasswords(): iterable
    {
        yield [PasswordStrength::STRENGTH_WEAK, 'test1234'];
        yield [PasswordStrength::STRENGTH_MEDIUM, 'kimai-timetrack'];
        yield [PasswordStrength::STRENGTH_STRONG, 'kimai-time-tracking'];
        yield [PasswordStrength::STRENGTH_VERY_STRONG, 'Kimai-Time-Tracking-2'];
    }

    #[DataProvider('getInvalidPasswords')]
    public function testWeakPasswordIsInvalid(int $strength, string $password): void
    {
        $this->setStrength($strength);
        $this->validator->validate($password, new PasswordPolicy());

        $this->buildViolation('The password strength is too low. Please use a stronger password.')
            ->setCode(PasswordStrength::PASSWORD_STRENGTH_ERROR)
            ->assertRaised();
    }
}
