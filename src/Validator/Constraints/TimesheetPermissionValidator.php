<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet;
use App\Security\CurrentUser;
use App\Validator\Constraints\TimesheetPermission as TimesheetPermissionConstraint;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TimesheetPermissionValidator extends ConstraintValidator
{
    /**
     * @var CurrentUser
     */
    protected $user;
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $auth;

    /**
     * @param CurrentUser $user
     * @param AuthorizationCheckerInterface $auth
     */
    public function __construct(CurrentUser $user, AuthorizationCheckerInterface $auth)
    {
        $this->user = $user;
        $this->auth = $auth;
    }

    /**
     * @param Timesheet $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetPermissionConstraint)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\TimesheetPermission');
        }

        if (!is_object($value) || !($value instanceof Timesheet)) {
            return;
        }

        $this->validateBeginAndEnd($value, $this->context);
    }

    /**
     * @param Timesheet $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateBeginAndEnd(Timesheet $timesheet, ExecutionContextInterface $context)
    {
        if (null === $timesheet->getEnd() && (null === $timesheet->getDuration() || 0 === $timesheet->getDuration())) {
            if (!$this->auth->isGranted('start', $timesheet)) {
                $context->buildViolation('You are not allowed to start this timesheet record.')
                    ->atPath('end')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetPermissionConstraint::START_DISALLOWED)
                    ->addViolation();

                return;
            }
        }

        // TODO check active entries against hard_limit
    }
}
