<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Classes using this MUST extend \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 */
trait EntityValidationTestTrait
{
    /**
     * @param object $entity
     * @param array<string>|string $fieldNames
     */
    public function assertHasViolationForField(object $entity, array|string $fieldNames, $groups = null): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($entity, null, $groups);

        if (!\is_array($fieldNames)) {
            $fieldNames = [$fieldNames];
        }

        $expected = \count($fieldNames);
        $actual = $violations->count();

        $violatedFields = [];
        /** @var ConstraintViolationInterface $validation */
        foreach ($violations as $validation) {
            $violatedFields[$validation->getPropertyPath()] = $validation->getPropertyPath();
        }
        $countViolations = \count($violatedFields);

        foreach ($fieldNames as $id => $propertyPath) {
            $foundField = false;
            if (\in_array($propertyPath, $violatedFields)) {
                $foundField = true;
                unset($violatedFields[$propertyPath]);
            }

            self::assertTrue($foundField, 'Failed finding violation for field: ' . $propertyPath);
        }

        self::assertEmpty($violatedFields, \sprintf('Unexpected violations found: %s', implode(', ', $violatedFields)));
        self::assertEquals($expected, $countViolations, \sprintf('Expected %s violations, found %s in %s.', $expected, $actual, implode(', ', array_keys($violatedFields))));
    }

    public function assertHasNoViolations($entity, $groups = null): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');

        $violations = $validator->validate($entity, null, $groups);
        $actual = $violations->count();

        self::assertEquals(0, $actual, \sprintf('Expected 0 violations, found %s.', $actual));
    }
}
