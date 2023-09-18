<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\Duration;
use App\Validator\Constraints\DurationValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\Duration
 * @covers \App\Validator\Constraints\DurationValidator
 * @extends ConstraintValidatorTestCase<DurationValidator>
 */
class DurationValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DurationValidator
    {
        return new DurationValidator();
    }

    /**
     * @return array<array<string|int|null>>
     */
    public function getValidData(): array
    {
        return [
            ['2h'],
            ['38m'],
            ['99s'],
            ['2h38m'],
            ['2h38s'],
            ['2m38s'],
            ['2h38m17s'],
            ['1h96m137s'],
            [''],
            ['0'],
            ['1.2'],
            ['2,3'],
            [null],
            [0],
            [11257200],
            ['13:27'],
            ['13:27:54'],
            ['12:87:54'],
            ['3127:00:00'],
            ['3127:00'],
            [48474],
        ];
    }

    public function testConstraintIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    /**
     * @dataProvider getValidData
     */
    public function testConstraintWithValidData(string|int|null $input): void
    {
        $constraint = new Duration();
        $this->validator->validate($input, $constraint);
        if ($input !== null) {
            $this->validator->validate(strtoupper($input), $constraint);
        }
        $this->assertNoViolation();
    }

    /**
     * @return array<array<string>>
     */
    public function getInvalidData(): array
    {
        return [
            ['13-13'],
            ['2m3m'],
            ['2s3s'],
            ['2h3h'],
            ['2m3h'],
            ['2s3h'],
            ['2s3m'],
            ['3127::00'],
            ['3127:00:'],
            [':3127:00'],
            ['::3127'],
            ['foo'],
        ];
    }

    /**
     * @dataProvider getInvalidData
     */
    public function testValidationError(string $input): void
    {
        $constraint = new Duration([
            'message' => 'myMessage',
        ]);

        $this->validator->validate($input, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"' . $input . '"')
            ->setParameter('{{ pattern }}', '/^-?[0-9]{1,}$|^-?[0-9]{1,}[,.]{1}[0-9]{1,}$|^-?[0-9]{1,}:[0-9]{1,}:[0-9]{1,}$|^-?[0-9]{1,}:[0-9]{1,}$|^[0-9]{1,}[hHmMsS]{1}$|^[0-9]{1,}[hH]{1}[0-9]{1,}[mM]{1}$|^[0-9]{1,}[hHmM]{1}[0-9]{1,}[sS]{1}$|^[0-9]{1,}[mM]{1}[0-9]{1,}[sS]{1}$|^[0-9]{1,}[hH]{1}[0-9]{1,}[mM]{1}[0-9]{1,}[sS]{1}$/')
            ->setCode(Regex::REGEX_FAILED_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider getInvalidData
     */
    public function testValidationErrorUpperCase(string $input): void
    {
        $input = strtoupper($input);
        $constraint = new Duration([
            'message' => 'myMessage',
        ]);

        $this->validator->validate($input, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"' . $input . '"')
            ->setParameter('{{ pattern }}', '/^-?[0-9]{1,}$|^-?[0-9]{1,}[,.]{1}[0-9]{1,}$|^-?[0-9]{1,}:[0-9]{1,}:[0-9]{1,}$|^-?[0-9]{1,}:[0-9]{1,}$|^[0-9]{1,}[hHmMsS]{1}$|^[0-9]{1,}[hH]{1}[0-9]{1,}[mM]{1}$|^[0-9]{1,}[hHmM]{1}[0-9]{1,}[sS]{1}$|^[0-9]{1,}[mM]{1}[0-9]{1,}[sS]{1}$|^[0-9]{1,}[hH]{1}[0-9]{1,}[mM]{1}[0-9]{1,}[sS]{1}$/')
            ->setCode(Regex::REGEX_FAILED_ERROR)
            ->assertRaised();
    }
}
