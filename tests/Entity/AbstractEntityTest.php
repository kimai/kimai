<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;

/**
 * @covers \App\Entity\Timesheet
 */
abstract class AbstractEntityTest extends TestCase
{
    /**
     * @param $value
     * @param array|string $fieldNames
     */
    protected function assertHasViolationForField($value, $fieldNames)
    {
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $validations = $validator->validate($value);

        if (!is_array($fieldNames)) {
            $fieldNames = [$fieldNames];
        }

        $expected = count($fieldNames);
        $actual = $validations->count();

        $this->assertEquals($expected, $actual, sprintf('Expected %s violations, found %s.', $expected, $actual));

        $violatedFields = [];
        /** @var ConstraintViolationInterface $validation */
        foreach ($validations as $validation) {
            $violatedFields[] = $validation->getPropertyPath();
        }

        foreach ($fieldNames as $id => $propertyPath) {
            $foundField = false;
            if (in_array($propertyPath, $violatedFields)) {
                $foundField = true;
                unset($violatedFields[$id]);
            }

            $this->assertTrue($foundField, 'Failed finding violation for field: ' . $propertyPath);
        }

        $this->assertEmpty($violatedFields, sprintf('Unexpected violations found: %s', implode(', ', $violatedFields)));
    }
}
