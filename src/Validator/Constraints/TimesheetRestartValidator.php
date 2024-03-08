<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet as TimesheetEntity;
use App\Timesheet\TrackingModeService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetRestartValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security,
        private readonly TrackingModeService $trackingModeService
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetRestart)) {
            throw new UnexpectedTypeException($constraint, TimesheetRestart::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        // special case that would otherwise need to be validated in several controllers:
        // an entry is edited and the end date is removed (or duration deleted) would restart the record,
        // which might be disallowed for the current user
        if (null !== $value->getEnd()) {
            return;
        }

        if ($this->context->getViolations()->count() > 0) {
            return;
        }

        if (null !== $this->security->getUser() && $this->security->isGranted('start', $value)) {
            return;
        }

        $mode = $this->trackingModeService->getActiveMode();
        $path = 'start_date';

        if ($mode->canEditEnd()) {
            $path = 'end_date';
        } elseif ($mode->canEditDuration()) {
            $path = 'duration';
        }

        $this->context->buildViolation('You are not allowed to start this timesheet record.')
            ->atPath($path)
            ->setTranslationDomain('validators')
            ->setCode(TimesheetRestart::START_DISALLOWED)
            ->addViolation();
    }
}
