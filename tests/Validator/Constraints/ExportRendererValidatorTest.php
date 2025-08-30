<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\ExportRenderer;
use App\Validator\Constraints\ExportRendererValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ExportRendererValidator>
 */
#[CoversClass(ExportRenderer::class)]
#[CoversClass(ExportRendererValidator::class)]
class ExportRendererValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ExportRendererValidator
    {
        return new ExportRendererValidator();
    }

    public static function getValidColors(): iterable
    {
        yield ['csv'];
        yield ['xlsx'];
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
        $constraint = new ExportRenderer();
        $this->validator->validate($color, $constraint);
        $this->assertNoViolation();
    }

    public static function getInvalidColors(): iterable
    {
        yield ['CSV'];
        yield ['XLSX'];
        yield ['PDF'];
        yield ['HTML'];
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
        $constraint = new ExportRenderer();

        $this->validator->validate($color, $constraint);

        if (\is_string($color)) {
            $expectedFormat = '"' . $color . '"';
        } else {
            $expectedFormat = $parameterType ?? '';
        }

        $this->buildViolation('Unknown exporter type.')
            ->setParameter('{{ value }}', $expectedFormat)
            ->setCode(ExportRenderer::UNKNOWN_TYPE)
            ->assertRaised();
    }
}
