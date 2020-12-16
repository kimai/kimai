<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\AllowedHtmlTags;
use App\Validator\Constraints\AllowedHtmlTagsValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\AllowedHtmlTags
 * @covers \App\Validator\Constraints\AllowedHtmlTagsValidator
 */
class AllowedHtmlTagsTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new AllowedHtmlTagsValidator();
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testConstraintIsInvalidObject()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new AllowedHtmlTags(['tags' => '']);
        $this->validator->validate(new \stdClass(), $constraint);
    }

    /**
     * @dataProvider getValidValues
     * @param string $allowedTags
     * @param string $testString
     */
    public function testConstraintWithValidValue(string $allowedTags, string $testString)
    {
        $constraint = new AllowedHtmlTags(['tags' => $allowedTags]);
        $this->validator->validate($testString, $constraint);
        $this->assertNoViolation();
    }

    public function testNullIsInvalid()
    {
        $this->validator->validate(null, new AllowedHtmlTags(['tags' => '<i>', 'message' => 'myMessage']));

        $this->assertNoViolation();
    }

    public function getValidValues()
    {
        return [
            ['', 'foo'],
            ['', ''],
            ['<i>', 'foo<i>kjhg</i>'],
            ['<i>', 'foo<I>kjhg</I>'],
            ['<u><i>', 'foo<i>kj<u>h</u>g</i><u>kjhgk</u>'],
        ];
    }

    public function getInvalidValues()
    {
        return [
            ['', 'foo<i>kjhg</i>'],
            ['<u>', 'foo<i>kjhg</i>'],
            ['<i>', 'foo<u>kjhg</u>'],
        ];
    }

    /**
     * @dataProvider getInvalidValues
     * @param string $allowedTags
     * @param string $testString
     */
    public function testValidationError(string $allowedTags, string $testString)
    {
        $constraint = new AllowedHtmlTags([
            'tags' => $allowedTags,
        ]);

        $this->validator->validate($testString, $constraint);

        $this->buildViolation('This string contains invalid HTML tags.')
            ->setParameter('{{ value }}', '"' . $testString . '"')
            ->setCode(AllowedHtmlTags::DISALLOWED_TAGS_FOUND)
            ->assertRaised();
    }
}
