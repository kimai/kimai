<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\TimesheetConfiguration;
use App\Form\MultiUpdate\TimesheetMultiUpdateDTO;
use App\Validator\Constraints\TimesheetMultiUpdate as TimesheetConstraint;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TimesheetMultiUpdateValidator extends ConstraintValidator
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $auth;
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;

    /**
     * @param AuthorizationCheckerInterface $auth
     * @param TimesheetConfiguration $configuration
     */
    public function __construct(AuthorizationCheckerInterface $auth, TimesheetConfiguration $configuration)
    {
        $this->auth = $auth;
        $this->configuration = $configuration;
    }

    /**
     * @param TimesheetMultiUpdateDTO|mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetConstraint)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\TimesheetMultiUpdate');
        }

        if (!is_object($value) || !($value instanceof TimesheetMultiUpdateDTO)) {
            return;
        }

        $this->validateActivityAndProject($value, $this->context);
        
        if (null !== $value->getFixedRate() && null !== $value->getHourlyRate()) {
            $this->context->buildViolation('Cannot set hourly rate and fixed rate at the same time')
                ->atPath('fixedRate')
                ->setTranslationDomain('validators')
                ->addViolation();
            
            $this->context->buildViolation('Cannot set hourly rate and fixed rate at the same time')
                ->atPath('hourlyRate')
                ->setTranslationDomain('validators')
                ->addViolation();
        }
    }

    /**
     * @param TimesheetMultiUpdateDTO $dto
     * @param ExecutionContextInterface $context
     */
    protected function validateActivityAndProject(TimesheetMultiUpdateDTO $dto, ExecutionContextInterface $context)
    {
        $activity = $dto->getActivity();
        $project = $dto->getProject();

        // non global activity without project
        if (null !== $activity && null !== $activity->getProject() && null === $project) {
            $context->buildViolation('Missing project')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::MISSING_PROJECT_ERROR)
                ->addViolation();
        }

        // only project was chosen
        if (null === $activity && null !== $project) {
            $context->buildViolation('Missing activity')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::MISSING_ACTIVITY_ERROR)
                ->addViolation();
        }

        if (null !== $activity) {
            if (null !== $activity->getProject() && $activity->getProject() !== $project) {
                $context->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
                    ->atPath('project')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::ACTIVITY_PROJECT_MISMATCH_ERROR)
                    ->addViolation();
            }

            if (!$activity->isVisible()) {
                $context->buildViolation('Cannot assign a disabled activity.')
                    ->atPath('activity')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::DISABLED_ACTIVITY_ERROR)
                    ->addViolation();
            }
        }

        if (null !== $project) {
            if (!$project->isVisible()) {
                $context->buildViolation('Cannot assign a disabled project.')
                    ->atPath('project')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::DISABLED_PROJECT_ERROR)
                    ->addViolation();
            }

            if (!$project->getCustomer()->isVisible()) {
                $context->buildViolation('Cannot assign a disabled customer.')
                    ->atPath('customer')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::DISABLED_CUSTOMER_ERROR)
                    ->addViolation();
            }
        }
    }
}
