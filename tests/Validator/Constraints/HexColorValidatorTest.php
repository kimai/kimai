<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\HexColor;
use App\Validator\Constraints\HexColorValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<HexColorValidator>
 */
#[CoversClass(HexColor::class)]
#[CoversClass(HexColorValidator::class)]
class HexColorValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): HexColorValidator
    {
        return new HexColorValidator();
    }

    public static function getValidColors(): iterable
    {
        yield ['#000'];
        yield ['#aaa'];
        yield ['#000000'];
        yield ['#fff000'];
        yield ['#000aaa'];
        yield ['#fffaaa'];
        yield ['']; // should actually be invalid, but it was allowed in the past :(
        yield [null];
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('#000', new NotBlank());
    }

    #[DataProvider('getValidColors')]
    public function testConstraintWithValidColor(?string $color): void
    {
        $constraint = new HexColor();
        $this->validator->validate($color, $constraint);
        $this->assertNoViolation();
    }

    public static function getInvalidColors(): iterable
    {
        yield ['string'];
        yield ['000'];
        yield ['aaa'];
        yield ['000000'];
        yield ['fff000'];
        yield ['000aaa'];
        yield ['fffaaa'];
        yield ['#f'];
        yield ['#ff'];
        yield ['#ffdd'];
        yield ['#ffddd'];
        yield ['#ffddddd'];
        yield [new \stdClass(), 'object'];
        yield [[], 'array'];
    }

    #[DataProvider('getInvalidColors')]
    public function testValidationError(mixed $color, ?string $parameterType = null): void
    {
        $constraint = new HexColor();

        $this->validator->validate($color, $constraint);

        if (\is_string($color)) {
            $expectedFormat = '"' . $color . '"';
        } else {
            $expectedFormat = $parameterType ?? '';
        }

        $this->buildViolation('The given value is not a valid hexadecimal color.')
            ->setParameter('{{ value }}', $expectedFormat)
            ->setCode(HexColor::HEX_COLOR_ERROR)
            ->assertRaised();
    }
}
