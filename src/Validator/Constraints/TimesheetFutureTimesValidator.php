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

final class TimesheetFutureTimesValidator extends ConstraintValidator
{
    public function __construct(private SystemConfiguration $configuration)
    {
    }

    /**
     * @param TimesheetEntity $value
     * @param Constraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetFutureTimes)) {
            throw new UnexpectedTypeException($constraint, TimesheetFutureTimes::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        if ($this->configuration->isTimesheetAllowFutureTimes()) {
            return;
        }

        $now = new \DateTime('now', $value->getBegin()->getTimezone());

        // allow configured default rounding time + 1 minute - see #1295
        $allowedDiff = ($this->configuration->getTimesheetDefaultRoundingBegin() * 60) + 60;
        $nowTs = $now->getTimestamp() + $allowedDiff;
        if ($value->getBegin() !== null && $nowTs < $value->getBegin()->getTimestamp()) {
            $this->context->buildViolation(TimesheetFutureTimes::getErrorName(TimesheetFutureTimes::BEGIN_IN_FUTURE_ERROR))
                ->atPath('begin_date')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetFutureTimes::BEGIN_IN_FUTURE_ERROR)
                ->addViolation();
        }

        $allowedDiff = ($this->configuration->getTimesheetDefaultRoundingEnd() * 60) + 60;
        $nowTs = $now->getTimestamp() + $allowedDiff;
        if ($value->getEnd() !== null && $nowTs < $value->getEnd()->getTimestamp()) {
            $this->context->buildViolation(TimesheetFutureTimes::getErrorName(TimesheetFutureTimes::END_IN_FUTURE_ERROR))
                ->atPath('end_time')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetFutureTimes::END_IN_FUTURE_ERROR)
                ->addViolation();
        }
    }
}
