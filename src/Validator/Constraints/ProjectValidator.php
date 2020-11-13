<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Project;
use App\Validator\Constraints\Project as ProjectConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ProjectValidator extends ConstraintValidator
{
    /**
     * @param Project|mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof ProjectConstraint)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Project');
        }

        if (!\is_object($value) || !($value instanceof Project)) {
            return;
        }

        $this->validateProject($value, $this->context);
    }

    protected function validateProject(Project $project, ExecutionContextInterface $context)
    {
        if (null !== $project->getStart() && null !== $project->getEnd() && $project->getStart()->getTimestamp() > $project->getEnd()->getTimestamp()) {
            $context->buildViolation('End date must not be earlier then start date.')
                ->atPath('end')
                ->setTranslationDomain('validators')
                ->setCode(ProjectConstraint::END_BEFORE_BEGIN_ERROR)
                ->addViolation();
        }
    }
}
