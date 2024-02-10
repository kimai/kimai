<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\ColorChoices;
use App\Validator\Constraints\ColorChoicesValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\ColorChoices
 * @covers \App\Validator\Constraints\ColorChoicesValidator
 * @extends ConstraintValidatorTestCase<ColorChoicesValidator>
 */
class ColorChoicesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ColorChoicesValidator
    {
        return new ColorChoicesValidator();
    }

    public function getValidColors()
    {
        yield ['#000000'];
        yield ['#fff000'];
        yield ['#000aaa'];
        yield ['#fffaaa'];
        yield ['Foo|#fffaaa,|#fffaaa,#fffaaa,Bar|#fffaaa,'];
        yield ['Fo o - sdsd|#fffaaa'];
        yield ['abcdefghijklmnopqrst|#fffaaa'];
        yield [''];
        yield [null];
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('#000', new NotBlank());
    }

    /**
     * @dataProvider getValidColors
     * @param string $color
     */
    public function testConstraintWithValidColor($color): void
    {
        $constraint = new ColorChoices();
        $this->validator->validate($color, $constraint);
        $this->assertNoViolation();
    }

    public function getInvalidColors()
    {
        yield ['sdf_sdf|#000000', null, 'sdf_sdf', '#000000'];
        yield ['sdfghjklöß.|#aaabbb', null, 'sdfghjklöß.', '#aaabbb'];
        yield ['abcdefghijklmnopqrstu|#aaabbb', null, 'abcdefghijklmnopqrstu', '#aaabbb'];
        yield ['string', 'string', null];
        yield ['000', '000', null];
        yield ['aaa', 'aaa', null];
        yield ['000000', '000000', null];
        yield ['fff000', 'fff000', null];
        yield ['000aaa', '000aaa', null];
        yield ['fffaaa', 'fffaaa', null];
        yield ['#f', '#f', null];
        yield ['#ff', '#ff', null];
        yield ['#ffdd', '#ffdd', null];
        yield ['#ffddd', '#ffddd', null];
        yield ['#ffddddd', '#ffddddd', null];
    }

    /**
     * @dataProvider getInvalidColors
     * @param string $color
     * @param string|null $invalidColor
     * @param string|null $invalidName
     * @param string|null $invalidNameCode
     */
    public function testValidationError(string $color, $invalidColor = null, $invalidName = null, $invalidNameCode = null): void
    {
        $constraint = new ColorChoices();

        $this->validator->validate($color, $constraint);

        if (null !== $invalidColor) {
            $this->buildViolation('The given value {{ value }} is not a valid hexadecimal color.')
                ->setParameter('{{ value }}', '"' . $invalidColor . '"')
                ->setCode(ColorChoices::COLOR_CHOICES_ERROR)
                ->assertRaised();
        }

        if (null !== $invalidName) {
            $this->buildViolation('The given value {{ name }} is not a valid color name for {{ color }}. Allowed are {{ max }} alpha-numerical characters, including minus and space.')
                ->setParameter('{{ color }}', '"' . ($invalidNameCode ?? $color) . '"')
                ->setParameter('{{ max }}', (string) $constraint->maxLength)
                ->setParameter('{{ count }}', (string) mb_strlen($invalidName))
                ->setParameter('{{ name }}', '"' . $invalidName . '"')
                ->setCode(ColorChoices::COLOR_CHOICES_NAME_ERROR)
                ->assertRaised();
        }

        if ($invalidColor === null && $invalidName === null) {
            $this->assertNoViolation();
        }
    }
}
