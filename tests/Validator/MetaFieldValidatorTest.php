<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator;

use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Validator\MetaFieldValidator;
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

#[CoversClass(MetaFieldValidator::class)]
class MetaFieldValidatorTest extends TestCase
{
    private function createSut(): MetaFieldValidator
    {
        return new MetaFieldValidator(Validation::createValidator());
    }

    public function testValidateWithoutConstraints(): void
    {
        $this->expectNotToPerformAssertions();

        $meta = (new CustomerMeta())->setName('homepage')->setType(UrlType::class);
        $this->createSut()->validate(new Customer('foo'), $meta, 'javascript:alert(1)');
    }

    /**
     * @return array<int, array<mixed>>
     */
    public static function getValidUrls(): array
    {
        return [
            [null],
            [''],
            ['https://www.kimai.org'],
            ['http://www.kimai.org/foo?bar=1#test'],
        ];
    }

    #[DataProvider('getValidUrls')]
    public function testValidateAcceptsValidValues(mixed $value): void
    {
        $this->expectNotToPerformAssertions();

        $meta = (new CustomerMeta())->setName('homepage')->setType(UrlType::class)->addConstraint(new Url());
        $this->createSut()->validate(new Customer('foo'), $meta, $value);
    }

    /**
     * @return array<int, array<string>>
     */
    public static function getInvalidUrls(): array
    {
        return [
            ['javascript:alert(1)'],
            ['javascript://%0Aalert(1)'],
            ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            ['www.kimai.org'],
        ];
    }

    #[DataProvider('getInvalidUrls')]
    public function testValidateRunsRegisteredConstraints(string $value): void
    {
        $meta = (new CustomerMeta())->setName('homepage')->setType(UrlType::class)->addConstraint(new Url());

        try {
            $this->createSut()->validate(new Customer('foo'), $meta, $value);
            self::fail('Expected ValidationFailedException for: ' . $value);
        } catch (ValidationFailedException $ex) {
            self::assertCount(1, $ex->getViolations());
            self::assertEquals('homepage', $ex->getViolations()->get(0)->getPropertyPath());
        }
    }

    /**
     * @return array<int, array<mixed>>
     */
    public static function getNonScalarValues(): array
    {
        return [
            [['https://www.kimai.org']],
            [[]],
            [new \stdClass()],
        ];
    }

    #[DataProvider('getNonScalarValues')]
    public function testValidateRejectsNonScalarValues(mixed $value): void
    {
        $meta = (new CustomerMeta())->setName('homepage')->setType(UrlType::class)->addConstraint(new Url())->addConstraint(new Length(['max' => 200]));

        try {
            $this->createSut()->validate(new Customer('foo'), $meta, $value);
            self::fail('Expected ValidationFailedException');
        } catch (ValidationFailedException $ex) {
            self::assertCount(1, $ex->getViolations());
            self::assertEquals('homepage', $ex->getViolations()->get(0)->getPropertyPath());
        }
    }

    public function testValidateDoesNotRunRegisteredConstraintsForNonScalarValues(): void
    {
        $called = false;
        $meta = (new CustomerMeta())->setName('homepage')->setType(TextType::class)->addConstraint(new Callback(function () use (&$called): void {
            $called = true;
        }));

        try {
            $this->createSut()->validate(new Customer('foo'), $meta, ['foo']);
            self::fail('Expected ValidationFailedException');
        } catch (ValidationFailedException) {
            self::assertFalse($called);
        }
    }

    #[DataProvider('getNonScalarValues')]
    public function testValidateRejectsNonScalarValuesWithoutConstraints(mixed $value): void
    {
        $meta = (new CustomerMeta())->setName('homepage')->setType(TextType::class);

        $this->expectException(ValidationFailedException::class);

        $this->createSut()->validate(new Customer('foo'), $meta, $value);
    }

    #[DataProvider('getNonScalarValues')]
    public function testValidateUserPreferenceRejectsNonScalarValues(mixed $value): void
    {
        $preference = (new UserPreference('foo'))->addConstraint(new Length(['max' => 200]));

        try {
            $this->createSut()->validateUserPreference(new User(), $preference, $value);
            self::fail('Expected ValidationFailedException');
        } catch (ValidationFailedException $ex) {
            self::assertCount(1, $ex->getViolations());
            self::assertEquals('foo', $ex->getViolations()->get(0)->getPropertyPath());
        }
    }

    public function testValidateRunsAllRegisteredConstraints(): void
    {
        $meta = (new CustomerMeta())
            ->setName('foo')
            ->setType(TextType::class)
            ->addConstraint(new Length(['max' => 3]))
            ->addConstraint(new Url());

        try {
            $this->createSut()->validate(new Customer('foo'), $meta, 'abcd');
            self::fail('Expected ValidationFailedException');
        } catch (ValidationFailedException $ex) {
            self::assertCount(2, $ex->getViolations());
        }
    }

    public function testValidateUsesEntityAsRoot(): void
    {
        $customer = new Customer('foo');
        $root = null;

        $meta = (new CustomerMeta())
            ->setName('foo')
            ->setType(TextType::class)
            ->addConstraint(new Callback(function (mixed $value, ExecutionContextInterface $context) use (&$root): void {
                $root = $context->getRoot();
            }));

        $this->createSut()->validate($customer, $meta, 'bar');

        self::assertSame($customer, $root);
    }

    public function testValidateDoesNotValidateEntity(): void
    {
        $this->expectNotToPerformAssertions();

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $sut = new MetaFieldValidator($validator);

        // invalid customer: the name must not be blank
        $meta = (new CustomerMeta())->setName('homepage')->setType(UrlType::class)->addConstraint(new Url());
        $sut->validate(new Customer(''), $meta, 'https://www.kimai.org');
    }

    public function testValidateUserPreferenceWithoutConstraints(): void
    {
        $this->expectNotToPerformAssertions();

        $this->createSut()->validateUserPreference(new User(), new UserPreference('foo'), -5);
    }

    public function testValidateUserPreferenceAcceptsValidValues(): void
    {
        $this->expectNotToPerformAssertions();

        $preference = (new UserPreference(UserPreference::HOURLY_RATE))->addConstraint(new Range(['min' => 0]));
        $sut = $this->createSut();

        $sut->validateUserPreference(new User(), $preference, null);
        $sut->validateUserPreference(new User(), $preference, '');
        $sut->validateUserPreference(new User(), $preference, 0);
        $sut->validateUserPreference(new User(), $preference, '12.5');
    }

    public function testValidateUserPreferenceRunsAllRegisteredConstraints(): void
    {
        $preference = (new UserPreference(UserPreference::HOURLY_RATE))
            ->addConstraint(new Range(['min' => 0]))
            ->addConstraint(new Length(['max' => 1]));

        try {
            $this->createSut()->validateUserPreference(new User(), $preference, -5);
            self::fail('Expected ValidationFailedException');
        } catch (ValidationFailedException $ex) {
            self::assertCount(2, $ex->getViolations());
            self::assertEquals(UserPreference::HOURLY_RATE, $ex->getViolations()->get(0)->getPropertyPath());
        }
    }

    public function testValidateUserPreferenceUsesUserAsRoot(): void
    {
        $user = new User();
        $root = null;

        $preference = (new UserPreference('foo'))
            ->addConstraint(new Callback(function (mixed $value, ExecutionContextInterface $context) use (&$root): void {
                $root = $context->getRoot();
            }));

        $this->createSut()->validateUserPreference($user, $preference, 'bar');

        self::assertSame($user, $root);
    }
}
