<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet as TimesheetEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetZeroDurationValidator extends ConstraintValidator
{
    public function __construct(private SystemConfiguration $configuration)
    {
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate(mixed $timesheet, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetZeroDuration)) {
            throw new UnexpectedTypeException($constraint, TimesheetZeroDuration::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($timesheet, TimesheetEntity::class);
        }

        if ($this->configuration->isTimesheetAllowZeroDuration()) {
            return;
        }

        if ($timesheet->isRunning()) {
            return;
        }

        $duration = 0;
        if ($timesheet->getEnd() !== null && $timesheet->getBegin() !== null) {
            $duration = $timesheet->getEnd()->getTimestamp() - $timesheet->getBegin()->getTimestamp();
        }

        if ($duration <= 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('duration')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetZeroDuration::ZERO_DURATION_ERROR)
                ->addViolation();
        }
    }
}
