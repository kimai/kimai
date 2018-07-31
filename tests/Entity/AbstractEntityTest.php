<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @covers \App\Entity\Timesheet
 */
abstract class AbstractEntityTest extends KernelTestCase
{
    /**
     * @param $entity
     * @param array|string $fieldNames
     */
    protected function assertHasViolationForField($entity, $fieldNames)
    {
        self::bootKernel();
        $validator = static::$kernel->getContainer()->get('validator');

        $violations = $validator->validate($entity);

        if (!is_array($fieldNames)) {
            $fieldNames = [$fieldNames];
        }

        $expected = count($fieldNames);
        $actual = $violations->count();

        $this->assertEquals($expected, $actual, sprintf('Expected %s violations, found %s.', $expected, $actual));

        $violatedFields = [];
        /** @var ConstraintViolationInterface $validation */
        foreach ($violations as $validation) {
            $violatedFields[$validation->getPropertyPath()] = $validation->getPropertyPath();
        }

        foreach ($fieldNames as $id => $propertyPath) {
            $foundField = false;
            if (in_array($propertyPath, $violatedFields)) {
                $foundField = true;
                unset($violatedFields[$propertyPath]);
            }

            $this->assertTrue($foundField, 'Failed finding violation for field: ' . $propertyPath);
        }

        $this->assertEmpty($violatedFields, sprintf('Unexpected violations found: %s', implode(', ', $violatedFields)));
    }

    protected function assertHasNoViolations($entity)
    {
        self::bootKernel();
        $validator = static::$kernel->getContainer()->get('validator');

        $violations = $validator->validate($entity);
        $actual = $violations->count();

        $this->assertEquals(0, $actual, sprintf('Expected 0 violations, found %s.', $actual));
    }
}
