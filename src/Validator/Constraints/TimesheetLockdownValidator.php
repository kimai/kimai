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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetLockdownValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security,
        private readonly LockdownService $lockdownService
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetLockdown)) {
            throw new UnexpectedTypeException($constraint, TimesheetLockdown::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        if (!$this->lockdownService->isLockdownActive()) {
            return;
        }

        if (null === ($timesheetStart = $value->getBegin())) {
            return;
        }

        // lockdown never takes effect for users with special permission
        if (null !== $this->security->getUser() && $this->security->isGranted('lockdown_override_timesheet')) {
            return;
        }

        $now = new \DateTime('now', $timesheetStart->getTimezone());

        if (!empty($constraint->now)) {
            if ($constraint->now instanceof \DateTimeInterface) {
                $now = $constraint->now;
            } elseif (\is_string($constraint->now)) {
                try {
                    $now = new \DateTime($constraint->now, $timesheetStart->getTimezone());
                } catch (\Exception $ex) {
                }
            }
        }

        $allowEditInGracePeriod = false;
        if (null !== $this->security->getUser() && $this->security->isGranted('lockdown_grace_timesheet')) {
            $allowEditInGracePeriod = true;
        }

        if ($this->lockdownService->isEditable($value, $now, $allowEditInGracePeriod)) {
            return;
        }

        // raise a violation for all entries before the start of lockdown period
        $this->context->buildViolation('This period is locked, please choose a later date.')
            ->atPath('begin_date')
            ->setTranslationDomain('validators')
            ->setCode(TimesheetLockdown::PERIOD_LOCKED)
            ->addViolation();
    }
}
