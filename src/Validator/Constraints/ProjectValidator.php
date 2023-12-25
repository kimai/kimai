<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Project;
use App\Validator\Constraints\Project as ProjectEntityConstraint;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ProjectValidator extends ConstraintValidator
{
    /**
     * @param ProjectConstraint[] $constraints
     */
    public function __construct(
        #[TaggedIterator(ProjectConstraint::class)]
        private iterable $constraints = []
    )
    {
    }

    /**
     * @param Project|mixed $value
     * @param Constraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof ProjectEntityConstraint)) {
            throw new UnexpectedTypeException($constraint, ProjectEntityConstraint::class);
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
            $context->buildViolation(ProjectEntityConstraint::getErrorName(ProjectEntityConstraint::END_BEFORE_BEGIN_ERROR))
                ->atPath('end')
                ->setTranslationDomain('validators')
                ->setCode(ProjectEntityConstraint::END_BEFORE_BEGIN_ERROR)
                ->addViolation();
        }
    }
}
