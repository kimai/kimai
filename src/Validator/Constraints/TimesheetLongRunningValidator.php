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

final class TimesheetLongRunningValidator extends ConstraintValidator
{
    public function __construct(private readonly SystemConfiguration $systemConfiguration)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetLongRunning)) {
            throw new UnexpectedTypeException($constraint, TimesheetLongRunning::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        if ($value->isRunning()) {
            return;
        }

        /** @var int $duration */
        $duration = $value->getCalculatedDuration();

        // one year is currently the maximum that can be logged (which is already not logically)
        // the database column could hold more data, but let's limit it here
        if ($duration > 31536000) {
            $this->context->buildViolation($constraint->maximumMessage)
                ->setTranslationDomain('validators')
                ->atPath('duration')
                ->setCode(TimesheetLongRunning::MAXIMUM)
                ->addViolation();

            return;
        }

        $maxMinutes = $this->systemConfiguration->getTimesheetLongRunningDuration();

        if ($maxMinutes <= 0) {
            return;
        }

        // float on purpose, because one second more than the configured minutes is already too long
        $minutes = $duration / 60;

        // allow maximum of the exact configured minutes
        if ($minutes <= $maxMinutes) {
            return;
        }

        $format = new \App\Utils\Duration();
        $hours = $format->format($maxMinutes * 60);

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $hours)
            ->setTranslationDomain('validators')
            ->atPath('duration')
            ->setCode(TimesheetLongRunning::LONG_RUNNING)
            ->addViolation();
    }
}
