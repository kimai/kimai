<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Entity\Project;
use App\Validator\Constraints\Project as ProjectConstraint;
use App\Validator\Constraints\ProjectValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\Constraints\ProjectValidator
 */
class ProjectValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new ProjectValidator();
    }

    public function testConstraintIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', new NotBlank());
    }

    public function testEndBeforeStartIsInvalid()
    {
        $begin = new \DateTime();
        $end = new \DateTime('-1 hour');
        $project = new Project();
        $project->setStart($begin);
        $project->setEnd($end);

        $this->validator->validate($project, new ProjectConstraint());

        $this->buildViolation('End date must not be earlier then start date.')
            ->atPath('property.path.end')
            ->setCode(ProjectConstraint::END_BEFORE_BEGIN_ERROR)
            ->assertRaised();
    }
}
