<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * @covers \App\Entity\Timesheet
 */
class TimesheetTest extends TestCase
{
    protected function getEntity()
    {
        $customer = new Customer();
        $customer->setName('Test Customer');

        $project = new Project();
        $project->setName('Test Project');
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setName('Test');
        $activity->setProject($project);

        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity($activity);

        return $entity;
    }

    public function testValidationEndNotEarlierThanBegin()
    {
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $end = $end->modify('-1 second');
        $entity->setBegin($begin);
        $entity->setEnd($end);

        $this->assertHasViolationForField($entity, 'end');

        // allow same begin and end
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $entity->setBegin($begin);
        $entity->setEnd($end);

        $this->assertHasViolationForField($entity, []);
    }

    public function testDurationMustBeGreatorOrEqualThanZero()
    {
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $entity->setBegin($begin);
        $entity->setEnd($end);
        $entity->setDuration(-1);

        $this->assertHasViolationForField($entity, 'duration');

        // allow zero duration
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $entity->setBegin($begin);
        $entity->setEnd($end);
        $entity->setDuration(0);

        $this->assertHasViolationForField($entity, []);
    }

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

        $expected = count($fieldNames);
        $actual = $validations->count();

        $this->assertEquals($expected, $actual, sprintf('Expected %s violations, found %s.', $expected, $actual));
    }
}
