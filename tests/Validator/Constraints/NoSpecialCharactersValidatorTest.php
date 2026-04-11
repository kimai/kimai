<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\NoHtmlSpecialCharacters;
use App\Validator\Constraints\NoHtmlSpecialCharactersValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<NoHtmlSpecialCharactersValidator>
 */
#[CoversClass(NoSpecialCharacters::class)]
#[CoversClass(NoSpecialCharactersValidator::class)]
class NoSpecialCharactersValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NoHtmlSpecialCharactersValidator
    {
        return new NoHtmlSpecialCharactersValidator();
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testGetTargets(): void
    {
        $constraint = new NoHtmlSpecialCharacters();
        self::assertEquals('property', $constraint->getTargets());
    }

    public static function getValidTestData(): array
    {
        return [
            [''],
            [null],
            ['asdf-.,123!§$%&/()=?`4567\'890ß'],
        ];
    }

    #[DataProvider('getValidTestData')]
    public function testValidInput(string|null $data): void
    {
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate($data, new NoHtmlSpecialCharacters());

        $this->assertNoViolation();
    }

    public static function getInvalidTestData(): array
    {
        return [
            ['Test" onclick="alert(1)"'],
            ['Test><a href=#>Foo</a>'],
            ['Test" broken string'],
        ];
    }

    #[DataProvider('getInvalidTestData')]
    public function testInvalidInput(string|null $data): void
    {
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate($data, new NoHtmlSpecialCharacters());

        $this->buildViolation('These characters are not allowed: {{ chars }}')
            ->setCode(NoHtmlSpecialCharacters::SPECIAL_CHARACTERS_FOUND)
            ->setParameter('{{ chars }}', '< " >')
            ->assertRaised();
    }
}
