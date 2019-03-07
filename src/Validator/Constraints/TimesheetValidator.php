<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet;
use App\Validator\Constraints\Timesheet as TimesheetConstraint;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TimesheetValidator extends ConstraintValidator
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $auth;
    /**
     * @var array
     */
    protected $rules = [];
    /**
     * @var bool
     */
    protected $durationOnly = false;

    /**
     * @param AuthorizationCheckerInterface $auth
     * @param array $ruleset
     * @param bool $durationOnly
     */
    public function __construct(AuthorizationCheckerInterface $auth, array $ruleset, bool $durationOnly)
    {
        $this->auth = $auth;
        $this->rules = $ruleset;
        $this->durationOnly = $durationOnly;
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    protected function getRule(string $key, $default = null)
    {
        if (!isset($this->rules[$key])) {
            return $default;
        }

        return $this->rules[$key];
    }

    /**
     * @param Timesheet $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetConstraint)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Timesheet');
        }

        if (!is_object($value) || !($value instanceof Timesheet)) {
            return;
        }

        $this->validateBeginAndEnd($value, $this->context);
        $this->validateActivityAndProject($value, $this->context);
        $this->validatePermissions($value, $this->context);
    }

    /**
     * @param Timesheet $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validatePermissions(Timesheet $timesheet, ExecutionContextInterface $context)
    {
        // special case that would otherwise need to be validated in several controllers:
        // an entry is edited and the end date is removed (or duration deleted) would restart the record,
        // which might be disallowed for the current user
        if ($context->getViolations()->count() == 0 && null === $timesheet->getEnd()) {
            if (!$this->auth->isGranted('start', $timesheet)) {
                $context->buildViolation('You are not allowed to start this timesheet record.')
                    ->atPath($this->durationOnly ? 'duration' : 'end')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::START_DISALLOWED)
                    ->addViolation();

                return;
            }
        }

        // TODO check active entries against hard_limit
    }

    /**
     * @param Timesheet $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateBeginAndEnd(Timesheet $timesheet, ExecutionContextInterface $context)
    {
        if (null === $timesheet->getBegin()) {
            $context->buildViolation('You must submit a begin date.')
                ->atPath('begin')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::MISSING_BEGIN_ERROR)
                ->addViolation();

            return;
        }

        if (null !== $timesheet->getBegin() && null !== $timesheet->getEnd() && $timesheet->getEnd()->getTimestamp() < $timesheet->getBegin()->getTimestamp()) {
            $context->buildViolation('End date must not be earlier then start date.')
                ->atPath('end')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::END_BEFORE_BEGIN_ERROR)
                ->addViolation();
        }

        if (false === $this->getRule('allow_future_times', true) && time() < $timesheet->getBegin()->getTimestamp()) {
            $context->buildViolation('The begin date cannot be in the future.')
                ->atPath('begin')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::BEGIN_IN_FUTURE_ERROR)
                ->addViolation();
        }
    }

    /**
     * @param Timesheet $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateActivityAndProject(Timesheet $timesheet, ExecutionContextInterface $context)
    {
        if (null === ($activity = $timesheet->getActivity())) {
            $context->buildViolation('A timesheet must have an activity.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::MISSING_ACTIVITY_ERROR)
                ->addViolation();
        }

        if (null === ($project = $timesheet->getProject())) {
            $context->buildViolation('A timesheet must have a project.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::MISSING_PROJECT_ERROR)
                ->addViolation();
        }

        if (null === $activity || null === $project) {
            return;
        }

        if (null !== $activity->getProject() && $activity->getProject() !== $project) {
            $context->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::ACTIVITY_PROJECT_MISMATCH_ERROR)
                ->addViolation();
        }

        if (null === $timesheet->getEnd() && $activity->getVisible() === false) {
            $context->buildViolation('Cannot start a disabled activity.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::DISABLED_ACTIVITY_ERROR)
                ->addViolation();
        }

        if (null === $timesheet->getEnd() && $project->getVisible() === false) {
            $context->buildViolation('Cannot start a disabled project.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::DISABLED_PROJECT_ERROR)
                ->addViolation();
        }

        if (null === $timesheet->getEnd() && $project->getCustomer()->getVisible() === false) {
            $context->buildViolation('Cannot start a disabled customer.')
                ->atPath('customer')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::DISABLED_CUSTOMER_ERROR)
                ->addViolation();
        }
    }
}
