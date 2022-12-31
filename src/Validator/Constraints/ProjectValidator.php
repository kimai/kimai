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

final class ProjectValidator extends ConstraintValidator
{
    /**
     * @param Constraint[] $constraints
     */
    public function __construct(private iterable $constraints = [])
    {
    }

    /**
     * @param Project|mixed $value
     * @param Constraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof ProjectConstraint)) {
            throw new UnexpectedTypeException($constraint, ProjectConstraint::class);
        }

        if (!\is_object($value) || !($value instanceof Project)) {
            return;
        }

        $this->validateProject($value, $this->context);

        foreach ($this->constraints as $innerConstraint) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($value, $innerConstraint, [Constraint::DEFAULT_GROUP]);
        }
    }

    protected function validateProject(Project $project, ExecutionContextInterface $context): void
    {
        if (null !== $project->getStart() && null !== $project->getEnd() && $project->getStart()->getTimestamp() > $project->getEnd()->getTimestamp()) {
            $context->buildViolation(ProjectConstraint::getErrorName(ProjectConstraint::END_BEFORE_BEGIN_ERROR))
                ->atPath('end')
                ->setTranslationDomain('validators')
                ->setCode(ProjectConstraint::END_BEFORE_BEGIN_ERROR)
                ->addViolation();
        }
    }
}
