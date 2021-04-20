<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet as TimesheetEntity;
use App\Timesheet\LockdownService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetLockdownValidator extends ConstraintValidator
{
    private $auth;
    private $lockdownService;

    public function __construct(AuthorizationCheckerInterface $auth, LockdownService $lockdownService)
    {
        $this->auth = $auth;
        $this->lockdownService = $lockdownService;
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate($timesheet, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetLockdown)) {
            throw new UnexpectedTypeException($constraint, TimesheetLockdown::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($timesheet, TimesheetEntity::class);
        }

        if (!$this->lockdownService->isLockdownActive()) {
            return;
        }

        if (null === ($timesheetStart = $timesheet->getBegin())) {
            return;
        }

        // lockdown never takes effect for users with special permission
        if ($this->auth->isGranted('lockdown_override_timesheet')) {
            return;
        }

        $now = new \DateTime('now', $timesheetStart->getTimezone());

        if (!empty($constraint->now)) {
            if ($constraint->now instanceof \DateTime) {
                $now = $constraint->now;
            } elseif (\is_string($constraint->now)) {
                try {
                    $now = new \DateTime($constraint->now, $timesheetStart->getTimezone());
                } catch (\Exception $ex) {
                }
            }
        }

        $allowEditInGracePeriod = $this->auth->isGranted('lockdown_grace_timesheet');

        if ($this->lockdownService->isEditable($timesheet, $now, $allowEditInGracePeriod)) {
            return;
        }

        // raise a violation for all entries before the start of lockdown period
        $this->context->buildViolation('This period is locked, please choose a later date.')
            ->atPath('begin')
            ->setTranslationDomain('validators')
            ->setCode(TimesheetLockdown::PERIOD_LOCKED)
            ->addViolation();
    }
}
