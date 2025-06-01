<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet as TimesheetEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetNegativeDurationValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetNegativeDuration)) {
            throw new UnexpectedTypeException($constraint, TimesheetNegativeDuration::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        if ($value->isRunning()) {
            return;
        }

        $duration = $value->getCalculatedDuration();

        if ($duration !== null && $duration < 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('duration')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetNegativeDuration::NEGATIVE_DURATION_ERROR)
                ->addViolation();
        }
    }
}
